$(function(){
    socket = new io.Socket('localhost', {port: 8888, resource: 'echoTest'});
    socket.connect();
    socket.send('some data');
    socket.on('message', function(data){
        alert('got some data' + data);
    });
});