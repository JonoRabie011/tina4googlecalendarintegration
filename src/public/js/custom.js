$(function() {

    $('#create-event-form #dateTimeStart').daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: true,
        showDropdowns: true,
        minYear: 2023,
        maxYear: parseInt(moment().format('YYYY'),10),
        startDate: moment().startOf('hour').add(60, 'minutes'),
        endDate: moment().startOf('hour').add(1, 'hour'),
        locale: {
            format: 'dddd, MMM DD HH:mm'
        }
    });

    $('#create-event-form #dateTimeEnd').daterangepicker({
        singleDatePicker: true,
        timePicker: true,
        timePicker24Hour: true,
        showDropdowns: true,
        minYear: 2023,
        maxYear: parseInt(moment().format('YYYY'),10),
        startDate: moment().startOf('hour').add(90, 'minutes'),
        locale: {
            format: 'dddd, MMM DD HH:mm'
        }
    });

    $('#create-event-form #timeZone').val(Intl.DateTimeFormat().resolvedOptions().timeZone);

    $('#eventGuests').selectize({
        create: true,
        valueField: "value",
        labelField: "name",
        sortField: "name",
        searchField: "name",
        delimiter: ",",
        selectOnTab: false,
        onItemAdd: function (){
            $('#eventGuests-selectized').focus();
        }
    });
});


function showSpinner(targetDiv,show=true){
    let spinner = '<div class="text-center spinner-border" role="status">' +
                      '<span class="visually-hidden"></span>' +
                  '</div>';
    if(show){
        $('#' + targetDiv).html(spinner);
    }else{
        $('#' + targetDiv).html('');
    }
}

function eventDetails(calendarId, eventId, linkKey, linkValue){
    // alert('clicked');
    $('#eventDetails').modal('show');
    showSpinner('eventDetails .modal-body');
    // // let data = getFormData('moeketsidominic@gmail.com65h62phg6sr34b9gc9gj8b9kchj3ab9p69i3ab9jchhm8p346hhj2d336c');
    // let url = '/google/events/get/'+calendarId+'/'+eventId+'/'+linkKey+'/'+linkValue;
    // $.ajax({
    //     method: 'POST',
    //     url: url,
    //     data: 'calendarId='+calendarId+'&eventId='+eventId,
    //     processData: false,
    //     contentType: false
    // }).done(function (data) {
    //     // modalBodyContent.html(data.message);
    //     // primaryButton.html('Confirm');
    //     // primaryButton.prop("disabled", false);
    //     // let originalOnclick = primaryButton.attr('onclick');
    //     // let onClick = 'saveActionForm(\''+formName + '\',' + '\'/folder/delete/true\',\'' + targetDiv + '\',\'' + modal + '\')';
    //     // primaryButton.attr('onclick', onClick);
    //     // $('#' + modal).on('hide.bs.modal', function () {
    //     //     primaryButton.html('Delete Folder');
    //     //     primaryButton.attr('onclick', originalOnclick);
    //     // });
    //     console.log(data);
    // });
    saveForm(calendarId + eventId,
             '/google/events/get/'+calendarId+'/'+eventId+'/'+linkKey+'/'+linkValue,
             'eventDetails .modal-body');
}