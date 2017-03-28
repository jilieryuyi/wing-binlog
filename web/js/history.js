/**
 * Created by yuyi on 17/1/4.
 */
var History = {
    key:"wing-binlog-visit-history",
    add:function(){
        var history = this.get();
        if(history.length>0)
        {
            if(history[history.length-1]!=window.location.href)
            history.push(window.location.href);
        }else{
            history.push(window.location.href);
        }
        if( history.length > 3 ){
            history.shift();
        }
        Cookies.set(this.key,history);
    },
    get:function(){
        var json = Cookies.get(this.key);
        if( typeof json == "undefined" )
            return [];
        return JSON.parse( json );
    },
    back:function(){
        var history = this.get();
        if(history.length > 1 )
        {
            history.pop();
            window.location.href = history[history.length-1];
        }
        else{
            window.location.href = "/";
        }
    }
};
(function(){
    History.add();
})();