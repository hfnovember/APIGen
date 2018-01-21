
function myAJAXFunc() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            //Success:
            console.log(this.responseText);
        }
    };
    xhttp.open("POST", "http://localhost:8080/Generated/API/Users/Create/", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("Username=TestUser&Password=1234&UserLevelID=1");
}