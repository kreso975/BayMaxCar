/**
 * Created by Kokanovic on 07/04/2017.
 */
var socket;

function init() {
    var host = "ws://192.168.1.98:9000/deamon.php"; // SET THIS TO YOUR SERVER

    try
    {
        socket = new WebSocket(host);

        socket.onopen = function(msg)
        {
            if(this.readyState == 1)
            {

            }
        };

        //Message received from websocket server
        socket.onmessage = function(msg)
        {

        };

        //Connection closed
        socket.onclose = function(msg)
        {

        };

        socket.onerror = function()
        {

        }
    }

    catch(ex)
    {
    }

    $("msg").focus();
}

function send()
{
    var txt, msg;
    txt = $("msg");
    msg = txt.value;
    
    socket.send("1");

    txt.value="";
    txt.focus();

    try
    {
        socket.send(msg);
    }
    catch(ex)
    {
    }
}

function quit()
{
    if (socket != null)
    {
        socket.close();
        socket=null;
    }
}

function reconnect()
{
    quit();
    init();
}

// Utilities
function $(id)
{
    return document.getElementById(id);
}


function onkey(event)
{
    if(event.keyCode==13)
    {
        send();
    }
}
