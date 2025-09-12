function formSubmit(params) {
  $(`#${params.form_id}`).validate({
    rules: params.rules,
    messages: params.messages,

    submitHandler: function (form) {
      var submitButton = $(form).find('button[type="submit"]');
      submitButton.prop('disabled', true).text('Submitting...');
      var formData = new FormData(form);
      console.log('formSubmit', formData);

      $.ajax({
        url: $(form).attr('action'),
        type: $(form).attr('method'),
        headers: {
          'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        // data: $(form).serialize(),
        data: formData,
        contentType: false,
        processData: false,
        crossDomain: true,
        async: true,
        success: function (response) {
          console.log('response;', response);
          // Show success notification
          if (response.success) {
            submitButton.text('Success');

            toastr.success(response?.message);
            setTimeout(() => {
              if (response?.return_url) {
                window.location.href = response?.return_url;
              } else {
                location.reload();
              }
            }, 2000);
            // // Reset form
            $(form).resetForm();
          } else {
            toastr.warning(response?.message ?? 'Error!');

            submitButton.prop('disabled', false).text('Submit');
          }
        },
        error: function (xhr, status, error) {
          // Handle server-side validation errors
          submitButton.prop('disabled', false);
          submitButton.text('Submit');
          // var errors = xhr.responseJSON.errors;
          console.error('xhr:', xhr);
          console.error('status:', status);
          console.error('error:', error);
          toastr.error(error);
        }
      });
    }
  });
}

function ajaxPostRequest(url, jsonData, { success, onError }) {
  $.ajax({
    url: url, // The URL where the request will be sent
    type: 'POST', // The request type (POST)
    data: JSON.stringify(jsonData), // Convert the JSON object to a JSON string
    contentType: 'application/json', // Ensure content type is set to application/json
    processData: false, // Don't process data (needed for file uploads)
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
    success: function (response) {
      // Success callback
      if (typeof success === 'function') {
        success(response);
      }
    },
    error: function (xhr, status, error) {
      // Error callback
      console.error('AJAX request failed: ', error);
      if (typeof onError === 'function') {
        onError(error);
      }
    }
  });
}
