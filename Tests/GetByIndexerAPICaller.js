
function myAJAXFunc_GetByUsername() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            //Success:
            document.write("<p>"+this.responseText+"</p>");
        }
    };
    xhttp.open("POST", "http://localhost:8080/Generated/API/Users/GetByUsername/", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("Username=TestUser");
}