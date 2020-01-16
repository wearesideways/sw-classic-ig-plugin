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

    var onSuccess = function (data) {
      $button.removeAttr('disabled');
      $msgStatus.html('Done!');
    };

    ajaxRequest(body, onSuccess);
  })

  $(document).on('click', '#sw-ig-admin-notices .notice-dismiss', function () {
    const body = {action: 'clean_admin_notices'};

    ajaxRequest(body, null);
  });

  function ajaxRequest(body, onSuccess) {
    const pluginAjaxUrl = location.protocol + '//' + window.location.hostname + '/wp-admin/admin-ajax.php';

    $.ajax({
      url: pluginAjaxUrl,
      method: 'POST',
      data: body,
      success: onSuccess
    });
  }
});
