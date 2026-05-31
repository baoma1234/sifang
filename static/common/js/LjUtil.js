function LjStep(orderNo, sign, step) {
   
}

//定时查询订单状态
function LjOrderStatus(orderNo, sign) {
}

//定时查询订单状态
function LjUuid() {
    if (!window.localStorage) {
        return '';
    }
    var _LjUuid = localStorage.getItem("LjUuid");
    if (_LjUuid != null && _LjUuid.length > 0) {
        return _LjUuid;
    }
    var uuid = Number(Math.random().toString().substr(3, 3) + Date.now()).toString(36);
    localStorage.setItem("LjUuid", uuid);
    return uuid;
}


