jQuery(function ($) {
  $('.sw-ig-plugin-sync-feed').click(function () {
    const $button = $(this),
      $msgStatus = $button.next('.sw-ig-account-panel-status');

    $button.attr('disabled', 'disabled');
    $msgStatus.html('Importing... This could take a while');

    const body = {
      action: 'sync_feed',
      access_token: $button.data('access-token'),
      ig: $button.data('access-token')
    };

    const onSuccess = (response) => {
      if (response.success === true) {
        $button.removeAttr('disabled');
        $msgStatus.html('Done!');
      } else {
        onError()
      }
    };

    const onError = () => {
      $button.removeAttr('disabled');
      $msgStatus.html('There was an issue during the import, try again.');

      setTimeout(function () {
        location.reload();
      }, 4000)
    };

    ajaxRequest(body, onSuccess, onError);
  })

  $(document).on('click', '#sw-ig-admin-notices .notice-dismiss', function () {
    const body = { action: 'clean_admin_notices' };

    ajaxRequest(body, null);
  });

  $('#sw-ig-autosync-field').click(function() {
    const body = {
      action: 'update_autosync_option',
      autosync: this.checked,
    }

    ajaxRequest(body, null, () => alert('There was an issue during settings update. Try again.'))
  })

  const ajaxRequest = (body, onSuccess, onError = null) => {
    const pluginAjaxUrl = vars.ajaxurl;

    $.ajax({
      url: pluginAjaxUrl,
      method: 'POST',
      data: body,
      success: onSuccess,
      error: onError
    });
  }
});
