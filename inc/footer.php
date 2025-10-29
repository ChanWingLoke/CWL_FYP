 <!-- Main Footer -->
 
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script
  src="http://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollbars -->
<script src="plugins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>

<!-- OPTIONAL SCRIPTS -->
<!-- <script src="dist/js/demo.js"></script> -->

<!-- PAGE PLUGINS -->
<!-- jQuery Mapael -->
<script src="plugins/jquery-mousewheel/jquery.mousewheel.js"></script>
<script src="plugins/raphael/raphael.min.js"></script>
<script src="plugins/jquery-mapael/jquery.mapael.min.js"></script>
<script src="plugins/jquery-mapael/maps/usa_states.min.js"></script>
<!-- Datatable JS -->
<script src="//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js"></script>
<!-- ChartJS -->
<script src="plugins/chart.js/Chart.min.js"></script>
<!-- datepicker js  -->
<script src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
<!-- select 2 js  -->
<script src="plugins/select2/js/select2.min.js"></script>
<!-- PAGE SCRIPTS -->
<?php if (isset($current) && $current === 'dashboard'): ?>
  <script src="dist/js/pages/dashboard2.js"></script>
<?php endif; ?>
<script src="assets/js/custom.js"></script>
<script src="assets/js/ajax_req.js"></script>

<script>
  $(document).ready(function(){
    $('.dropdown-submenu a.test').on("click", function(e){
      $(this).next('ul').toggle();
      e.stopPropagation();
      e.preventDefault();
    });
  });
</script>
<script>
   $(document).ready(function($) {
     $('.select2').select2();
   });
</script>

<!-- datepicker (global defaults) -->
<script>
  if ($.fn.datepicker) {
    // Set global defaults for ALL datepickers
    $.fn.datepicker.defaults.format = 'yyyy-mm-dd';
    $.fn.datepicker.defaults.autoclose = true;
    $.fn.datepicker.defaults.todayHighlight = true;
    $.fn.datepicker.defaults.orientation = 'bottom';
  }

  // Initialize any datepicker fields on the page
  $(function(){
    $('.datepicker').datepicker();
  });
</script>



<!-- google translate -->
<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');
}
</script>

<script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

<script type="text/javascript">
function googleTranslateElementInit() {
  new google.translate.TranslateElement({pageLanguage: 'en'}, 'google_translate_element');

  var $googleDiv = $("#google_translate_element .skiptranslate");
  var $googleDivChild = $("#google_translate_element .skiptranslate div");
  $googleDivChild.next().remove();

  $googleDiv.contents().filter(function(){
      return this.nodeType === 3 && $.trim(this.nodeValue) !== '';
  }).remove();

}
</script>

<!-- Clean preloader controller -->
<script>
(function() {
  const pre = document.getElementById('app-preloader');
  function hideLoader(){ if (pre) pre.classList.add('hidden'); }
  function showLoader(){ if (pre) pre.classList.remove('hidden'); }

  // Hide once the page is fully ready
  window.addEventListener('load', hideLoader);

  // Expose helpers for use anywhere
  window.showLoader = showLoader;
  window.hideLoader = hideLoader;
})();
</script>

</body>
</html>
