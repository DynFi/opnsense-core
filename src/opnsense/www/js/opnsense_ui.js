/**
 * User interface shared components, requires opnsense.js for supporting functions.
 */

/**
 * save form to server
 * @param url endpoint url
 * @param formid parent id to grep input data from
 */
function saveFormToEndpoint(url,formid) {
    data = getFormData(formid);
    ajaxCall(url=url,sendData=data,callback=function(data,status){
        if ( status == "success") {
            // if there are validation issues, update our screen and show a dialog.
            if (data['validations'] != undefined) {
                // update field validation
                handleFormValidation(formid,data['validations']);
                BootstrapDialog.show({
                    type:BootstrapDialog.TYPE_WARNING,
                    title: 'Input validation',
                    message: 'Please correct validation errors in form'
                });
            }
        } else {
            // error handling, show internal errors
            // Normally the form should only return validation issues, if other things go wrong throw an error.
            BootstrapDialog.show({
                type: BootstrapDialog.TYPE_ERROR,
                title: 'save',
                message: 'Unable to save data, an internal error occurred.<br> ' +
                'Response from server was: <br> <small>'+JSON.stringify(data)+'</small>'
            });
        }

    });

}