
$('.smallul li').hover(function(e) {
				$(this).find('.dropdown-menu').slideToggle();
			});
			
			
			$('.leftmenu>ul>li>a').click(function(e) {
				$('.leftmenu>ul>li>a').removeClass('on');
                $(this).addClass('on');
				$(this).next('ul').slideToggle();
            });
			
			$('.zhuangtai a').click(function(e) {
				$('.zhuangtai a').removeClass('on');
                $(this).addClass('on');
            });


















