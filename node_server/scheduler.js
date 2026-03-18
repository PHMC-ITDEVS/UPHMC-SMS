const schedule = require('node-schedule');

const { exec } = require("child_process");
exec_option = { detached: false, shell: false, windowsHide: true, maxBuffer: 1024 * 500 }

/*
    *    *    *    *    *    *
    ┬    ┬    ┬    ┬    ┬    ┬
    │    │    │    │    │    |
    │    │    │    │    │    └ day of week (0 - 7) (0 or 7 is Sun)
    │    │    │    │    └───── month (1 - 12)
    │    │    │    └────────── day of month (1 - 31)
    │    │    └─────────────── hour (0 - 23)
    │    └──────────────────── minute (0 - 59)
    └───────────────────────── second (0 - 59, OPTIONAL)
*/


// schedule.scheduleJob('0 * * * * *', function(){
//     execute_command("registration:notify");
// });
execute_command("queue:restart");

setTimeout(function() {       
    execute_command("queue:work");
    execute_command("queue:work --queue=excel_counter_queue");
    execute_command("queue:work --queue=revolving_queue");
}, 3000)

function execute_command(command)
{
    let date = new Date();

    exec(`php artisan ${command}`, exec_option, (error, stdout, stderr) => {
        if(error) {
            console.log(`error: ${error.message}`);
            return;
        }

        if(stderr) {
            console.log(`stderr: ${stderr}`);
            return;
        }
        console.log(`execute command:${command} at ${date}`);
    });
}

function execute_command2(command)
{

    let date = new Date();

    exec(`${command}`, exec_option, (error, stdout, stderr) => {
        if (error) {
            console.log(`error: ${error.message}`);
            return;
        }
        if (stderr) {
            console.log(`stderr: ${stderr}`);
            return;
        }
        console.log(`execute command:${command} at ${date}`);
    });
}

// let seconds = 30;
// setInterval(() => {
//     execute_command("verify:payment");
// }, seconds * 1e3);