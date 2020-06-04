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

    let onSuccess = () => {
      $button.removeAttr('disabled');
      $msgStatus.html('Done!');
      setTimeout(function () {
        location.reload();
      }, 2000)
    };

    let onError = () => {
      $button.removeAttr('disabled');
      $msgStatus.html('There was an issue during the import, try again.');
    };

    ajaxRequest(body, onSuccess, onError);
  })

  $(document).on('click', '#sw-ig-admin-notices .notice-dismiss', function () {
    const body = {action: 'clean_admin_notices'};

    ajaxRequest(body, null);
  });

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
