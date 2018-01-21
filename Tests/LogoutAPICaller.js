
function myAJAXFunc_Logout() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            //Success:
            document.write(this.responseText);
        }
    };
    xhttp.open("POST", "http://localhost:8080/Generated/API/Logout/", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("SessionID=68dc84fa248ef7433400d9715039c497");
}