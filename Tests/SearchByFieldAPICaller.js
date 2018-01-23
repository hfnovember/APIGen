
function myAJAXFunc_SBF() {
    var xhttp = new XMLHttpRequest();
    xhttp.onreadystatechange = function() {
        if (this.readyState === 4 && this.status === 200) {
            //Success:
            document.write("<p>"+this.responseText+"</p>");
        }
    };
    xhttp.open("POST", "http://localhost:8080/Generated/API/Users/SearchByField/", true);
    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
    xhttp.send("Fields=Username,UserLevelID&Values=Bravo,1");
}