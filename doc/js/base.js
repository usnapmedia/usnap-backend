$(function() {
    $('#content-wrapper #api-wrapper .endpoint-wrapper').click(function() {
        if($(this).hasClass('active')) {
            $(this).removeClass('active');
            $(this).find('.details').hide(); 
            $(this).find('.arrow img').attr('src', 'img/down.png');
        } else {
            $(this).addClass('active');
            $(this).find('.details').show();    
            $(this).find('.arrow img').attr('src', 'img/up.png');
        }
    });
    
    $('#add_another_parameter').click(function() {
    	var clone = $('#clone_param').clone().appendTo('#param_list').show();
    });
});