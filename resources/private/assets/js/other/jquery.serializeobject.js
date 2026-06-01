$.fn.serializeObject = function()
{
    var o = {};
    var a = this.find(':input').serializeArray();
    $.each(a, function() {
        // Basic true/false translation
        if (this.value == 'true') {
            this.value = true;
        } else if (this.value == 'false') {
            this.value = false;
        } else if (this.value === undefined) {
            this.value = '';
        }

        if (o[this.name] !== undefined) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value);
        } else {
            o[this.name] = this.value;
        }
    });
    return o;
};
