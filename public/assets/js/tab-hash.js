// code for maintaining the tab when switched
function showTabFromHash() {
  var hash = window.location.hash;
  if (hash) {
    var tabLink = $('.tab-content ' + hash);
    if (tabLink.length) {
      $('.nav-item .nav-link').removeClass('active');
      $('.nav-item ' + hash + '_link').addClass('active');
      $('.tab-content .tab-pane').removeClass('active');
      tabLink.addClass('show active');
    }
  }
}
// Event listener for tab click
$('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
  // var target = $(e.target).data('bs-target');
  var target = $(e.target).attr('href');
  window.location.hash = target;
});
// Show tab if there's a hash in the URL
showTabFromHash();
// Re-show tab when the hash changes
$(window).on('hashchange', function () {
  showTabFromHash();
});
// END tab maintaing
