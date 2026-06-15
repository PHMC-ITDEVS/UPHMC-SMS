#!/usr/bin/env python3
"""
UPHMC SMS - SIM inbox cleaner
scripts/sms/clean_sms_inbox.py

Reads all SMS messages from the modem SIM inbox, logs them to PostgreSQL,
then deletes them from the modem storage.

Exit codes:
    0  Success, or modem busy and skipped cleanly
    1  Runtime failure
    2  Setup error
"""

import argparse
import os
import re
import sys
import time
from dataclasses import dataclass
from datetime import datetime, timedelta, timezone
from typing import Optional

try:
    import serial
except ImportError:
    print(
        "ERROR: pyserial is not installed.\n"
        "Run: pip install -r scripts/sms/requirements.txt",
        file=sys.stderr,
    )
    sys.exit(2)

try:
    import psycopg2
except ImportError:
    print(
        "ERROR: psycopg2 is not installed.\n"
        "Run: pip install -r scripts/sms/requirements.txt",
        file=sys.stderr,
    )
    sys.exit(2)


BUSY_PATTERNS = (
    "busy",
    "+cme error: 515",
    "+cms error: 512",
)


@dataclass
class InboxMessage:
    index: int
    status: str
    sender: str
    timestamp: Optional[datetime]
    message: str


def is_busy_text(text: str) -> bool:
    lowered = text.lower()
    return any(pattern in lowered for pattern in BUSY_PATTERNS)


def send_at(ser: "serial.Serial", command: str, expect: str, timeout: int = 5) -> str:
    ser.write((command + "\r").encode("latin-1"))
    ser.flush()

    response = ""
    deadline = time.monotonic() + timeout

    while time.monotonic() < deadline:
        waiting = ser.in_waiting
        if waiting > 0:
            chunk = ser.read(waiting).decode("latin-1", errors="replace")
            response += chunk

            if expect in response:
                return response

            if is_busy_text(response):
                raise RuntimeError(f"MODEM_BUSY: {response.strip()}")

            if "ERROR" in response or "+CMS ERROR" in response or "+CME ERROR" in response:
                raise RuntimeError(
                    f"Modem returned error on command '{command}': {response.strip()}"
                )

        time.sleep(0.05)

    raise TimeoutError(
        f"Timeout ({timeout}s) waiting for '{expect}' after command '{command}'. "
        f"Got: '{response.strip()}'"
    )


def wake_modem(ser: "serial.Serial", attempts: int = 5) -> None:
    for _ in range(attempts):
        try:
            ser.reset_input_buffer()
            send_at(ser, "AT", "OK", timeout=2)
            return
        except RuntimeError as exc:
            if str(exc).startswith("MODEM_BUSY:"):
                raise
            time.sleep(0.2)
        except TimeoutError:
            time.sleep(0.2)

    raise RuntimeError(
        f"Modem on {ser.port} did not respond to AT after {attempts} wake attempts."
    )


def debug_log(message: str) -> None:
    print(f"DEBUG: {message}", file=sys.stderr)


def count_cmgl_records(response: str) -> int:
    normalized = response.replace("\r\n", "\n").replace("\r", "\n")
    return len(
        re.findall(
            r"^\+CMGL:\s*\d+,",
            normalized,
            flags=re.MULTILINE,
        )
    )


def has_cmgl_terminator(response: str) -> bool:
    normalized = (
        response
        .replace("\r\n", "\n")
        .replace("\r", "\n")
        .rstrip()
    )

    return normalized == "OK" or normalized.endswith("\nOK")


def read_all_messages(
    ser: "serial.Serial",
    timeout: int,
    idle_timeout: float = 5.0,
) -> str:
    ser.write(b'AT+CMGL="ALL"\r')
    ser.flush()

    response = ""
    bytes_received = 0
    messages_parsed = 0

    started_at = time.monotonic()
    last_data_at = started_at
    deadline = started_at + timeout

    while time.monotonic() < deadline:
        waiting = ser.in_waiting

        if waiting > 0:
            raw_chunk = ser.read(waiting)
            chunk = raw_chunk.decode(
                "latin-1",
                errors="replace",
            )

            response += chunk
            bytes_received += len(raw_chunk)

            last_data_at = time.monotonic()
            messages_parsed = count_cmgl_records(response)

            debug_log(
                "CMGL chunk "
                f"bytes={bytes_received} "
                f"messages={messages_parsed} "
                f"elapsed={last_data_at - started_at:.2f}s"
            )

            if has_cmgl_terminator(response):
                debug_log(
                    "CMGL completed with OK "
                    f"bytes={bytes_received} "
                    f"messages={messages_parsed} "
                    f"elapsed={time.monotonic() - started_at:.2f}s"
                )
                return response

            if is_busy_text(response):
                raise RuntimeError(
                    f"MODEM_BUSY: {response.strip()}"
                )

            if (
                "ERROR" in response
                or "+CMS ERROR" in response
                or "+CME ERROR" in response
            ):
                raise RuntimeError(
                    f"Failed to read SIM inbox: {response.strip()}"
                )

        else:
            idle_elapsed = time.monotonic() - last_data_at

            if (
                messages_parsed > 0
                and idle_elapsed >= idle_timeout
            ):
                debug_log(
                    "CMGL completed via idle timeout "
                    f"bytes={bytes_received} "
                    f"messages={messages_parsed} "
                    f"elapsed={time.monotonic() - started_at:.2f}s "
                    f"idle={idle_elapsed:.2f}s"
                )
                return response

        time.sleep(0.05)

    raise TimeoutError(
        "Timeout "
        f"({timeout}s) waiting for CMGL response. "
        f"bytes={bytes_received}, "
        f"messages={messages_parsed}, "
        f"partial='{response[:500]}'"
    )


def parse_timestamp(raw_value: str) -> Optional[datetime]:
    value = raw_value.strip()
    if not value:
        return None

    match = re.match(
        r"(?P<yy>\d{2})/(?P<mm>\d{2})/(?P<dd>\d{2}),(?P<hh>\d{2}):(?P<mi>\d{2}):(?P<ss>\d{2})(?P<tz>[+-]\d{2})?",
        value,
    )
    if not match:
        return None

    year = 2000 + int(match.group("yy"))
    month = int(match.group("mm"))
    day = int(match.group("dd"))
    hour = int(match.group("hh"))
    minute = int(match.group("mi"))
    second = int(match.group("ss"))

    tz_raw = match.group("tz")
    if tz_raw is not None:
        quarter_hours = int(tz_raw)
        offset = timedelta(minutes=quarter_hours * 15)
        tzinfo = timezone(offset)
    else:
        tzinfo = None

    return datetime(year, month, day, hour, minute, second, tzinfo=tzinfo)


def parse_cmgl_response(response: str) -> list[InboxMessage]:
    normalized = response.replace("\r\n", "\n").replace("\r", "\n")

    pattern = re.compile(
        r'^\+CMGL:\s*(?P<index>\d+),"(?P<status>[^"]*)","(?P<sender>[^"]*)","[^"]*","(?P<timestamp>[^"]*)"\n',
        re.MULTILINE,
    )

    matches = list(pattern.finditer(normalized))
    messages: list[InboxMessage] = []

    for idx, match in enumerate(matches):
        body_start = match.end()
        
        if idx + 1 < len(matches):
            body_end = matches[idx + 1].start()
        else:
            body_end = len(normalized)

        body = normalized[body_start:body_end].strip("\n")

        messages.append(
            InboxMessage(
                index=int(match.group("index")),
                status=match.group("status"),
                sender=match.group("sender"),
                timestamp=parse_timestamp(match.group("timestamp")),
                message=body,
            )
        )

    return messages


def connect_db():
    db_connection = os.getenv("DB_CONNECTION", "pgsql")
    if db_connection != "pgsql":
        raise RuntimeError(f"DB_CONNECTION must be 'pgsql', got '{db_connection}'")

    return psycopg2.connect(
        host=os.getenv("DB_HOST", "127.0.0.1"),
        port=os.getenv("DB_PORT", "5432"),
        dbname=os.getenv("DB_DATABASE"),
        user=os.getenv("DB_USERNAME"),
        password=os.getenv("DB_PASSWORD"),
        sslmode=os.getenv("DB_SSLMODE", "prefer"),
    )


def log_message(conn, inbox_message: InboxMessage, deleted_at: datetime) -> None:
    with conn.cursor() as cursor:
        cursor.execute(
            """
            INSERT INTO sms_inbox_logs (sender, message, received_at, deleted_at, created_at, updated_at)
            VALUES (%s, %s, %s, %s, NOW(), NOW())
            """,
            (
                inbox_message.sender,
                inbox_message.message,
                inbox_message.timestamp,
                deleted_at,
            ),
        )


def delete_message(ser: "serial.Serial", index: int, timeout: int) -> None:
    send_at(ser, f"AT+CMGD={index}", "OK", timeout=timeout)


def build_parser() -> argparse.ArgumentParser:
    parser = argparse.ArgumentParser(
        description="UPHMC SMS - read, log, and delete all messages from SIM inbox"
    )
    parser.add_argument("--port", required=True, help="Serial port e.g. COM3 or /dev/ttyUSB0")
    parser.add_argument("--baud", required=False, default=115200, type=int, help="Baud rate")
    parser.add_argument(
        "--timeout",
        required=False,
        default=120,
        type=int,
        help="Overall CMGL read timeout in seconds"
    )

    parser.add_argument(
        "--idle-timeout",
        required=False,
        default=5.0,
        type=float,
        help="Seconds without new modem data before CMGL is considered complete"
    )
    return parser


def main() -> None:
    parser = build_parser()
    args = parser.parse_args()

    if args.timeout <= 0:
        parser.error("--timeout must be greater than 0")

    if args.idle_timeout <= 0:
        parser.error("--idle-timeout must be greater than 0")

    try:
        ser = serial.Serial(
            port=args.port,
            baudrate=args.baud,
            bytesize=serial.EIGHTBITS,
            parity=serial.PARITY_NONE,
            stopbits=serial.STOPBITS_ONE,
            timeout=args.timeout,
            write_timeout=args.timeout,
            dsrdtr=True,
            rtscts=False,
            xonxoff=False,
        )
    except serial.SerialException as exc:
        if is_busy_text(str(exc)) or "permission" in str(exc).lower():
            print(f"SKIP: modem busy on {args.port}: {exc}")
            sys.exit(0)

        print(f"ERROR: Could not open port {args.port}: {exc}", file=sys.stderr)
        sys.exit(1)

    conn = None

    try:
        time.sleep(0.3)
        ser.reset_input_buffer()
        ser.reset_output_buffer()

        wake_modem(ser, attempts=5)
        send_at(ser, "AT+CMGF=1", "OK", timeout=args.timeout)
        send_at(ser, 'AT+CSCS="GSM"', "OK", timeout=args.timeout)

        response = read_all_messages(ser, timeout=args.timeout)
        messages = parse_cmgl_response(response)

        if not messages:
            print("OK: no SIM inbox messages found")
            sys.exit(0)

        conn = connect_db()
        conn.autocommit = False

        deleted_count = 0

        for inbox_message in messages:
            deleted_at = datetime.now(timezone.utc)
            log_message(conn, inbox_message, deleted_at)
            delete_message(ser, inbox_message.index, timeout=args.timeout)
            conn.commit()
            deleted_count += 1

        print(f"OK: logged and deleted {deleted_count} SIM inbox message(s)")
        sys.exit(0)

    except RuntimeError as exc:
        if str(exc).startswith("MODEM_BUSY:"):
            print(f"SKIP: modem busy, inbox cleanup deferred: {exc}")
            sys.exit(0)

        if conn is not None:
            conn.rollback()

        print(f"ERROR: {exc}", file=sys.stderr)
        sys.exit(1)

    except Exception as exc:
        if conn is not None:
            conn.rollback()

        print(f"ERROR: {exc}", file=sys.stderr)
        sys.exit(1)

    finally:
        if conn is not None:
            conn.close()
        ser.close()


if __name__ == "__main__":
    main()
