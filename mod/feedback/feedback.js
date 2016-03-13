function set_item_focus(itemid) {
    var item = document.getElementById(itemid);
    if(item){
        item.focus();
    }
}

function setcourseitemfilter(item, item_typ) {
    document.report.courseitemfilter.value = item;
    document.report.courseitemfiltertyp.value = item_typ;
    document.report.submit();
}


M.mod_feedback = {};

M.mod_feedback.init_sendmessage = function(Y) {
    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', 'checked');
        });
    }, '#checkall');

    Y.on('click', function(e) {
        Y.all('input.usercheckbox').each(function() {
            this.set('checked', '');
        });
    }, '#checknone');
};
