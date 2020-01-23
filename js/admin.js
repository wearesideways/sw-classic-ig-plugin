jQuery(function ($) {
  $('#sw-ig-plugin-sync-feed').click(function () {
    const $button = $(this),
      $msgStatus = $button.next('.sw-ig-account-panel-status');

    $button.attr('disabled', 'disabled');
    $msgStatus.html('Importing...');

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
      }, 1000)
    };

    ajaxRequest(body, onSuccess);
  })

  $(document).on('click', '#sw-ig-admin-notices .notice-dismiss', function () {
    const body = {action: 'clean_admin_notices'};

    ajaxRequest(body, null);
  });

  const ajaxRequest = (body, onSuccess) => {
    const pluginAjaxUrl = vars.ajaxurl;

    $.ajax({
      url: pluginAjaxUrl,
      method: 'POST',
      data: body,
      success: onSuccess
    });
  }
});
