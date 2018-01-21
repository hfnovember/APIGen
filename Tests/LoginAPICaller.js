
function myAJAXFunc_Login() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            //Success:
            document.write(this.responseText);
        }
    };
    xhttp.open("POST", "http://localhost:8080/Generated/API/Login/", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("Username=TestUser&Password=1234");
}