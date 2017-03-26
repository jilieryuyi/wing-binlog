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