/*
 * FaceList 2.0 - Facebook Style List Box
 *
 * Copyright (c) 2010 Ian Tearle (iantearle.com)
 * Original take by Xavier Domenech 
 * Original autocomplete  by Dylan Verheul, Dan G. Switzer, Anjesh Tuladhar, J�rn Zaefferer
 *
 * $Date: 2009-06-11 16:09:52 -0000 (Fri, 06 Nov 2008) 
 *
 */
(function($){
	$.fn.faceList = function(data, options) {
		var defaults = { 
			returnID: 			false,
			intro_text: 		"Enter Search Here",
			no_result: 			"No Results Found",
			start_value:		{},
			limit_warning: 		"No More Selections Are Allowed",
			selectedItem: 		"value", //name of object property
			selectedValues: 	"value", //name of object property
			searchObj:		 	"value", //comma separated list of object property names
			queryParam: 		"q",
			retrieveLimit: 		false, //number for 'limit' param on ajax request
			extraParams: 		"",
			matchCase: 			false,
			minChars: 			1,
			keyDelay: 			400,
			resultsHighlight: 	true,
			neverSubmit: 		false,
			selectionLimit: 	false,
		  	start: 				function(){},
		  	selectionClick: 	function(elem){},
		  	formatList: 		false, //callback function
		  	retrieveComplete: 	function(data){ return data; },
		  	resultClick: 		function(data){},
		  	resultsComplete: 	function(){}
	  	};  
	 	var opts = $.extend(defaults, options);	 	
		
		var data_type = "object";
		var data_count = 0;
		if(typeof data == "string") {
			data_type = "string";
			var req_string = data;
		} else {
			var org_data = data;
			for (k in data) if (data.hasOwnProperty(k)) data_count++;
		}
		if((data_type == "object" && data_count > 0) || data_type == "string"){
			return this.each(function(x){
				if(!opts.returnID){
					x = x+""+Math.floor(Math.random()*100);
				} else {
					x = opts.returnID;
				}
				opts.start.call(this);
				var input = $(this);
				input.attr("autocomplete","off").addClass("as-input").attr("id","as-input-"+x).val(opts.intro_text);
				var input_focus = false;
				
				input.wrap('<ul class="facelist-selections" id="facelist-selections-'+x+'"></ul>').wrap('<li class="facelist-original" id="facelist-original-'+x+'"></li>');
				var selections_holder = $("#facelist-selections-"+x);
				var org_li = $("#facelist-original-"+x);				
				var results_holder = $('<div class="facelist-results" id="facelist-results-'+x+'"></div>').hide();
				var results_ul =  $('<ul class="facelist-list"></ul>');
				var values_input = $('<input type="hidden" class="facelist-values" name="facelist_values" id="facelist-values-'+x+'" />');
				var prefill_value = "";
				if(typeof opts.start_value == "string"){
					var vals = opts.start_value.split(",");					
					for(var i=0; i < vals.length; i++){
						var v_data = {};
						v_data[opts.selectedValues] = vals[i];
						if(vals[i] != ""){
							add_selected_item(v_data, "000"+i);	
						}		
					}
					prefill_value = opts.start_value;
				} else {
					prefill_value = "";
					var prefill_count = 0;
					for (k in opts.start_value) if (opts.start_value.hasOwnProperty(k)) prefill_count++;
					if(prefill_count > 0){
						for(var i=0; i < prefill_count; i++){
							var new_v = opts.start_value[i][opts.selectedValues];
							if(new_v == undefined){ new_v = ""; }
							prefill_value = prefill_value+new_v+",";
							if(new_v != ""){
								add_selected_item(opts.start_value[i], "000"+i);	
							}		
						}
					}
				}
				if(prefill_value != ""){
					input.val("");
					values_input.val(prefill_value);
					$("li.facelist-selection-item", selections_holder).removeClass("selected");
				}
				input.after(values_input);
				selections_holder.click(function(){
					input_focus = true;
					input.focus();
				}).mousedown(function(){ input_focus = false; }).after(results_holder);	

				var timeout = null;
				var prev = "";
				var totalSelections = 0;
				
				input.focus(function(){			
					if($(this).val() == opts.intro_text && values_input.val() == ""){
						$(this).val("");
					} else if(input_focus){
						if($(this).val() != ""){
							results_ul.css("width",selections_holder.outerWidth());
							results_holder.show();
						}
					}
					input_focus = true;
					return true;
				}).blur(function(){
					if($(this).val() == "" && values_input.val() == "" && prefill_value == ""){
						$(this).val(opts.intro_text);
					} else if(input_focus){
						$("li.facelist-selection-item", selections_holder).removeClass("selected");
						results_holder.hide();
					} 				
				}).keydown(function(e) {
					lastKeyPressCode = e.keyCode;
					first_focus = false;
					switch(e.keyCode) {
						case 38: // up
							e.preventDefault();
							moveSelection("up");
							break;
						case 40: // down
							e.preventDefault();
							moveSelection("down");
							break;
						case 8:  // delete
							if(input.val() == ""){							
								var last = values_input.val().split(",");
								last = last[last.length - 2];
								selections_holder.children().not(org_li.prev()).removeClass("selected");
								if(org_li.prev().hasClass("selected")){
									values_input.val(values_input.val().replace(last+",",""));
									org_li.prev().remove();
								} else {
									opts.selectionClick.call(this, org_li.prev());
									org_li.prev().addClass("selected");		
								}
							}
							if(input.val().length == 1){
								results_holder.hide();
								 prev = "";
							}
							if($(":visible",results_holder).length > 0){
								if (timeout){ clearTimeout(timeout); }
								timeout = setTimeout(function(){ onKeyChange(); }, opts.keyDelay);
							}
							break;
						case 9:  // tab
						case 13: // return
							var active = $("li.active:first", results_holder);
							if(active.length > 0){
								active.click();
								results_holder.hide();
							}
							if(opts.neverSubmit || active.length > 0){
								e.preventDefault();
							}
							break;
						default:
							if(opts.selectionLimit && $("li.facelist-selection-item", selections_holder).length >= opts.selectionLimit){
								results_ul.html('<li class="facelist-message">'+opts.limit_warning+'</li>');
								results_holder.show();
							} else {
								if (timeout){ clearTimeout(timeout); }
								timeout = setTimeout(function(){ onKeyChange(); }, opts.keyDelay);
							}
							break;
					}
				});
				
				function onKeyChange() {
					if( lastKeyPressCode == 46 || (lastKeyPressCode > 8 && lastKeyPressCode < 32) ){ return results_holder.hide(); }
					var string = input.val().replace(/[\\]+|[\/]+/g,"");
					if (string == prev) return;
					prev = string;
					if (string.length >= opts.minChars) {
						selections_holder.addClass("loading");
						if(data_type == "string"){
							var limit = "";
							if(opts.retrieveLimit){
								limit = "&limit="+encodeURIComponent(opts.retrieveLimit);
							}
							$.getJSON(req_string+"?"+opts.queryParam+"="+encodeURIComponent(string)+limit+opts.extraParams, function(data){ 
								data_count = 0;
								var new_data = opts.retrieveComplete.call(this, data);
								for (k in new_data) if (new_data.hasOwnProperty(k)) data_count++;
								processDataQuery(new_data, string); 
							});
						} else {
							processDataQuery(org_data, string);
						}
					} else {
						selections_holder.removeClass("loading");
						results_holder.hide();
					}
				}
				var num_count = 0;
				function processDataQuery(data, query){
					if (!opts.matchCase){ query = query.toLowerCase(); }
					var matchCount = 0;
					results_holder.html(results_ul.html("")).hide();
					for(var i=0;i<data_count;i++){				
						var num = i;
						num_count++;
						var forward = false;
						if(opts.searchObj == "value") {
							var str = data[num].value;
						} else {	
							var str = "";
							var names = opts.searchObj.split(",");
							for(var y=0;y<names.length;y++){
								var name = $.trim(names[y]);
								str = str+data[num][name]+" ";
							}
						}
						if(str){
							if (!opts.matchCase){ str = str.toLowerCase(); }				
							if(str.search(query) != -1 && values_input.val().search(data[num][opts.selectedValues]+",") == -1){
								forward = true;
							}	
						}
						if(forward){
							var formatted = $('<li class="facelist-result-item" id="facelist-result-item-'+num+'"></li>').click(function(){
									var raw_data = $(this).data("data");
									var number = raw_data.num;
									if($("#facelist-selection-"+number, selections_holder).length <= 0){
										var data = raw_data.attributes;
										input.val("").focus();
										prev = "";
										values_input.val(values_input.val()+data[opts.selectedValues]+",");
										add_selected_item(data, number);
										opts.resultClick.call(this, raw_data);
										results_holder.hide();
									}
								}).mousedown(function(){ input_focus = false; }).mouseover(function(){
									$("li", results_ul).removeClass("active");
									$(this).addClass("active");
								}).data("data",{attributes: data[num], num: num_count});
							var this_data = $.extend({},data[num]);
							if (!opts.matchCase){ 
								var regx = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + query + ")(?![^<>]*>)(?![^&;]+;)", "gi");
							} else {
								var regx = new RegExp("(?![^&;]+;)(?!<[^<>]*)(" + query + ")(?![^<>]*>)(?![^&;]+;)", "g");
							}
							
							if(opts.resultsHighlight){
								this_data[opts.selectedItem] = this_data[opts.selectedItem].replace(regx,"<em>$1</em>");
							}
							if(!opts.formatList){
								formatted = formatted.html(this_data[opts.selectedItem]);
							} else {
								formatted = opts.formatList.call(this, this_data, formatted);	
							}
							results_ul.append(formatted);
							delete this_data;
							matchCount++;
							if(opts.retrieveLimit && opts.retrieveLimit == matchCount ){ break; }
						}
					}
					selections_holder.removeClass("loading");
					if(matchCount <= 0){
						results_ul.html('<li class="facelist-message">'+opts.no_result+'</li>');
					}
					results_ul.css("width", selections_holder.outerWidth());
					results_holder.show();
					opts.resultsComplete.call(this);
				}
				
				function add_selected_item(data, num){
					var item = $('<li class="facelist-selection-item" id="facelist-selection-'+num+'"></li>').click(function(){
							opts.selectionClick.call(this, $(this));
							selections_holder.children().removeClass("selected");
							$(this).addClass("selected");
						}).mousedown(function(){ input_focus = false; });
					var close = $('<a class="facelist-close">&times;</a>').click(function(){
							values_input.val(values_input.val().replace(data[opts.selectedValues]+",",""));
							item.remove();
							input_focus = true;
							input.focus();
							return false;
						});
					org_li.before(item.html(data[opts.selectedItem]).prepend(close));
				}
				
				function moveSelection(direction){
					if($(":visible",results_holder).length > 0){
						var lis = $("li", results_holder);
						if(direction == "down"){
							var start = lis.eq(0);
						} else {
							var start = lis.filter(":last");
						}					
						var active = $("li.active:first", results_holder);
						if(active.length > 0){
							if(direction == "down"){
							start = active.next();
							} else {
								start = active.prev();
							}	
						}
						lis.removeClass("active");
						start.addClass("active");
					}
				}
									
			});
		}
	}
})(jQuery);  	