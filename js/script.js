// JavaScript Document



jQuery(document).ready(function($) {

	/* *********************** */
	/* postboxes */
	/* *********************** */
	if($(".bytebunch_admin_page_container").length >= 1){
    postboxes.save_state = function(){
        return;
    };
    postboxes.save_order = function(){
        return;
    };
    postboxes.add_postbox_toggles();
  }

});
