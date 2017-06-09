$(function(){
  $('.ias-spinner').hide();
  if($(".qa-qlist-recent .qa-q-list").length && $(".qa-page-links-list").length) {
    if (material_lite) {
      window.recent_ias = $(".mdl-layout__content").ias({
        container: ".qa-qlist-recent .qa-q-list"
        ,item: ".qa-q-list-item"
        ,pagination: ".qa-page-links"
        ,next: ".qa-page-next"
        ,delay: 600
      });
    } else {
        window.recent_ias = $.ias({
            container: ".qa-qlist-recent .qa-q-list"
            ,item: ".qa-q-list-item"
            ,pagination: ".qa-page-links-list"
            ,next: ".qa-page-next"
            ,delay: 600
        });
        window.recent_ias.extension(new IASSpinnerExtension());
    }
    window.recent_ias.extension(new IASTriggerExtension({
        text: crq_lang.read_next,
        textPrev: crq_lang.read_previous,
        offset: 100,
    }));
    window.recent_ias.extension(new IASNoneLeftExtension({
        html: '<div class="ias_noneleft">最後の記事です</div>', // optionally
    }));
    window.recent_ias.on('load', function() {
        $('.ias-spinner').show();
    });
    window.recent_ias.on('noneLeft', function() {
        $('.ias-spinner').hide();
    });
  }
});
