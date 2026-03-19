#!/usr/bin/env python3
"""
UPHMC SMS — AT Command Sender
scripts/sms/sms_send.py

Sends a single SMS via a GSM modem using AT commands.
Uses pyserial which correctly handles Windows COM port blocking
via Win32 SetCommTimeouts() — something PHP streams cannot do.

Usage:
    python scripts/sms/sms_send.py --port COM3 --baud 115200 --to 09171234567 --message "Hello"

Exit codes:
    0  SMS sent successfully (stdout contains "OK: ...")
    1  Failed           (stderr contains reason)
    2  Setup error      (pyserial not installed, script args missing)

Install dependencies:
    pip install -r scripts/sms/requirements.txt
"""

import argparse
import sys
import time

# ── Dependency check ──────────────────────────────────────────────────────────
try:
    import serial
except ImportError:
    print(
        "ERROR: pyserial is not installed.\n"
        "Run: pip install -r scripts/sms/requirements.txt",
        file=sys.stderr,
    )
    sys.exit(2)


# ── AT command helpers ────────────────────────────────────────────────────────

def send_at(ser: "serial.Serial", command: str, expect: str, timeout: int = 5) -> str:
    """
    Write an AT command to the modem and wait for the expected response string.

    Args:
        ser:     Open pyserial Serial instance
        command: AT command without trailing CR (e.g. "AT+CMGF=1")
        expect:  String to wait for in the response (e.g. "OK", ">")
        timeout: Seconds before TimeoutError is raised

    Returns:
        Full response string from modem

    Raises:
        TimeoutError: If expected response not received within timeout
        Exception:    If modem returns ERROR or +CMS ERROR
    """
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

            if "ERROR" in response or "+CMS ERROR" in response:
                raise Exception(
                    f"Modem returned error on command '{command}': {response.strip()}"
                )

        time.sleep(0.05)  # 50ms poll — fast enough, low CPU

    raise TimeoutError(
        f"Timeout ({timeout}s) waiting for '{expect}' "
        f"after command '{command}'. Got: '{response.strip()}'"
    )


def wake_modem(ser: "serial.Serial", attempts: int = 5) -> None:
    """
    Send repeated AT pings until the modem responds with OK.
    Required for autobaud sync and DTR wake-up.

    Raises:
        Exception: If modem does not respond after all attempts
    """
    for attempt in range(1, attempts + 1):
        try:
            ser.reset_input_buffer()
            send_at(ser, "AT", "OK", timeout=2)
            return
        except (TimeoutError, Exception) as exc:
            time.sleep(0.2)

    raise Exception(
        f"Modem on {ser.port} did not respond to AT after {attempts} wake attempts. "
        "Check that the modem is powered and the correct port is configured."
    )


# ── Main ──────────────────────────────────────────────────────────────────────

def main() -> None:
    parser = argparse.ArgumentParser(
        description="UPHMC SMS — Send SMS via GSM modem AT commands"
    )
    parser.add_argument("--port",    required=True,               help="Serial port e.g. COM3 or /dev/ttyUSB0")
    parser.add_argument("--baud",    required=False, default=115200, type=int, help="Baud rate (default: 115200)")
    parser.add_argument("--to",      required=True,               help="Recipient phone number e.g. 09171234567")
    parser.add_argument("--message", required=True,               help="SMS message body (max 160 chars)")
    parser.add_argument("--timeout", required=False, default=10,  type=int, help="AT command timeout in seconds")
    parser.add_argument("--max-len", required=False, default=160, type=int, help="Max SMS length (default: 160)")

    args = parser.parse_args()

    body = args.message[: args.max_len]

    try:
        ser = serial.Serial(
            port=args.port,
            baudrate=args.baud,
            bytesize=serial.EIGHTBITS,
            parity=serial.PARITY_NONE,
            stopbits=serial.STOPBITS_ONE,
            timeout=args.timeout,
            write_timeout=args.timeout,
            # dsrdtr=True asserts DTR signal — required for Itegno W3800U to respond
            # Without DTR, the modem ignores all AT commands (same issue as Putty vs PHP)
            dsrdtr=True,
            rtscts=False,   # No CTS/RTS hardware flow control
            xonxoff=False,  # No XON/XOFF software flow control
        )

    except serial.SerialException as exc:
        print(f"ERROR: Could not open port {args.port}: {exc}", file=sys.stderr)
        sys.exit(1)

    try:
        # Allow port to settle and drain any leftover bytes
        time.sleep(0.3)
        ser.reset_input_buffer()
        ser.reset_output_buffer()

        # Wake modem
        wake_modem(ser, attempts=5)

        # Initialize modem for SMS text mode
        send_at(ser, "AT+CMGF=1",    "OK", timeout=args.timeout)
        send_at(ser, 'AT+CSCS="GSM"', "OK", timeout=args.timeout)

        # Start SMS — modem will respond with ">" prompt
        send_at(ser, f'AT+CMGS="{args.to}"', ">", timeout=args.timeout)

        # Send message body terminated with Ctrl+Z (chr 26)
        ser.write((body + chr(26)).encode("latin-1"))
        ser.flush()

        # Wait for +CMGS: confirmation (modem network send — can take up to 30s)
        response = ""
        deadline = time.monotonic() + 30

        while time.monotonic() < deadline:
            waiting = ser.in_waiting
            if waiting > 0:
                chunk = ser.read(waiting).decode("latin-1", errors="replace")
                response += chunk

                if "+CMGS:" in response:
                    print(f"OK: SMS sent to {args.to}. Modem response: {response.strip()}")
                    sys.exit(0)

                if "ERROR" in response or "+CMS ERROR" in response:
                    raise Exception(f"SMS send failed: {response.strip()}")

            time.sleep(0.05)

        raise TimeoutError(
            f"Timeout waiting for +CMGS confirmation after sending to {args.to}. "
            f"Partial response: {response.strip()}"
        )

    except Exception as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        sys.exit(1)

    finally:
        ser.close()


if __name__ == "__main__":
    main()
