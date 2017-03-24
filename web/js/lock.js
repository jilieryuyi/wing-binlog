/**
 * Created by yuyi on 17/3/24.
 */
var ____lock_status = false;


var Wing = {
    ____lock_status : false
};
Wing.lock = function() {
    if (Wing.____lock_status)
        return false;
    Wing.____lock_status = true;
    window.setTimeout(function(){
        Wing.____lock_status = false;
    },3000);
    return true;
};

Wing.unlock = function() {
    Wing.____lock_status = false;
}