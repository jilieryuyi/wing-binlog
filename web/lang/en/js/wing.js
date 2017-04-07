/**
 * Created by yuyi on 17/3/24.
 */
if (typeof Wing == "undefined") {
    var Wing = {
        ____lock_status: false
    };
} else {
    Wing.____lock_status = false;
}
Wing.lock = function() {
    if (Wing.____lock_status) {
        alert("Please wait a moment, because another process is running!");
        return false;
    }
    Wing.____lock_status = true;
    window.setTimeout(function(){
        Wing.____lock_status = false;
    },3000);
    return true;
};

Wing.unlock = function() {
    Wing.____lock_status = false;
};

if (typeof Wing.Error == "undefined") {
    //error code
    Wing.Error = {
        ERROR_LOGOUT : 4000,          //need login, jump login.php
        ERROR_NOT_ALLOW_ACCESS : 4005 //not allow access
    };
}


window.addEventListener('load', function(e) {
    window.applicationCache.addEventListener('updateready', function(e) {
        if (window.applicationCache.status == window.applicationCache.UPDATEREADY) {
            // Browser downloaded a new app cache.
            // Swap it in and reload the page to get the new hotness.
            window.applicationCache.swapCache();
            //if (confirm('A new version of this site is available. Load it?')) {
            window.location.reload();
            //}
        } else {
            // Manifest didn't changed. Nothing new to server.
        }
    }, false);
}, false);
/*
$(document).ready(function(){
    $(document).on("keydown", function(e){
        console.log(e.which , e.keyCode);
        if ((e.which || e.keyCode) == 116) {
            var href = window.location.href;
            if (href.indexOf("?") !== -1)
                href = href+"&"+new Date().getTime()+Math.random()*10000;
            else
                href = href+"?"+new Date().getTime()+Math.random()*10000;
            window.location.href = href;
        }
    });
});*/



