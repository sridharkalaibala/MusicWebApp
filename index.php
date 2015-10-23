<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title>Music Player Application</title>
	<style type="text/css">

		/* Layout */
		body {
			min-width: 630px;
		}

		#container {
			padding-left: 200px;
			padding-right: 190px;
		}
		
		#container .column {
			position: relative;
			float: left;
		}
		
		#center {
			padding: 10px 20px;
			width: 100%;
		}
		
		#left {
			width: 200px;
			padding: 0 10px;
			right: 240px;
			margin-left: -100%;
		}
		
		#right {
			width: 130px;
			padding: 0 10px;
			margin-right: -100%;
		}
		
		#footer {
			clear: both;
		}
		
		/* IE hack */
		* html #left {
			left: 150px;
		}

		/* Make the columns the same height as each other */
		#container {
			overflow: hidden;
		}

		#container .column {
			padding-bottom: 1001em;
			margin-bottom: -1000em;
		}

		/* Fix for the footer */
		* html body {
			overflow: hidden;
		}
		
		* html #footer-wrapper {
			float: left;
			position: relative;
			width: 100%;
			padding-bottom: 10010px;
			margin-bottom: -10000px;
			background: #fff;
		}

		/* Aesthetics */
		body {
			margin: 0;
			padding: 0;
			font-family:Sans-serif;
			line-height: 1.5em;
		}
		nav ul {
			list-style-type: none;
			margin: 0;
			padding: 0;
		}
		
		nav ul a {
			color: darkgreen;
			text-decoration: none;
		}

		#header, #footer {
			font-size: large;
			padding: 0.3em;
			background: #3b4151;
                        color: #fff;
                        text-align: center;
                        
		}

		#left {
			background: #F7FDEB;
                        font-size: 12px;
		}
		
		#right {
			background: #F7FDEB;
                         font-size: 12px;
		}

		#center {
			background: #fff;
                         font-size: 12px;
		}

		#container .column {
			padding-top: 1em;
		}
                a {
                    color: #fff;
                }
                .song {
                    width: 180px;
                    height: 100px;
                    float: left;
                    border: #3b4151 solid 1px;
                    padding: 5px;
                    margin: 5px;
                }
		
	</style>
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.4/jquery.min.js"></script>
</head>

<body>

	<header id="header"><p> Music Web Application </p></header>

	<div id="container">

		<main id="center" class="column">
			<article>
			
				<h1>Songs [ Total: <span id="totalCount"></span> ]</h1>
				
				<div id="songsList"> </div>
			
			</article>								
		</main>

		<nav id="left" class="column">
                    
                         <form  id="filterForm" action="" method="POST">
						 <input type="text"  name="search" placeholder="Search" value="" /> <br>
						<br> <strong> Sort:</strong> <input class="leftFilters"  type="radio" name="sort" value="Rating" checked >Rating<input class="leftFilters"   type="radio" name="sort" value="Album">Album<input  class="leftFilters"  type="radio" name="sort" value="Title">Title<br>
                          
						  <div id="filtersBlock">


								
						  </div>

			
                        </form>
                       
                        
                       
			

		</nav>

		<div id="right" class="column">
			
			
		</div>

	</div>

	<div id="footer-wrapper">
            <footer id="footer"><p>  </p></footer>
	</div>
        <script>
            
            $(document).ready(function(){
                getJSON('');
            });
			checkedCheckBox   = [];
			unCheckedCheckBox = [];
			$(document).on("change", ".leftFilters", function(){
						if(this.checked) {
							checkedCheckBox.push(this.id);
							getJSON($('#filterForm').serialize());
							var index = unCheckedCheckBox.indexOf(this.id);
							unCheckedCheckBox.splice(index, 1);
                         }
						 
						 if(!this.checked ) {
							unCheckedCheckBox.push(this.id);
							getJSON($('#filterForm').serialize());
							var index = checkedCheckBox.indexOf(this.id);
							checkedCheckBox.splice(index, 1);
                         }

				});
			function getJSON(formData){
				 $.ajax({ // ajax call starts
						  url: 'api.php', // JQuery loads serverside.php
						  data: formData , // Send value of the clicked button
						  dataType: 'json', // Choosing a JSON datatype
						})
						.done(function(data) { // Variable data contains the data we get from serverside
								buildFilters(data);
								getSongsList(data);
								$('#totalCount').html(data['hits']['total']);
								manageCheckBox()
						}).fail(function() {
							alert("Ajax failed to fetch data");

						});

			}
			
			
			
			function buildFilters(data){
			  var htmlBlock = '';
			  
				$.each(data['aggregations'], function(key, value) {
					htmlBlock +=  "<br> <strong> "+key+"</strong>  <br>"; 
					$.each(value[key]['buckets'], function(ikey, ivalue) {
						var filterValue = '' + ivalue['key'];
						htmlBlock += '<input type="checkbox" class="leftFilters" name="filters['+key+'][]" value="'+filterValue+'" id="'+key+'_'+filterValue.replace(/ /g,'')+'"  /> '+filterValue+'  [ '+ivalue['doc_count']+' ]   <br>   ';
					}); 
				 }); 
				$('#filtersBlock').html(htmlBlock);
			}
			
		    function getSongsList(data){
				var htmlBlock = '';
				$.each(data['hits']['hits'], function(key, value) {
						htmlBlock += '<div class="song"><strong> Title: </strong> '+value['_source']['Title']+'<br><strong> Album: </strong> '+value['_source']['Album']+' <br><strong> Rating: <span style="color: green; font-size: 20px; ">'+printStars(value['_source']['Rating'])+' </span> </strong><br></div>';
				 }); 
				$('#songsList').html(htmlBlock); 
			}
			
			function printStars(count){
				var stars = '';
				for(i=1; i<=count; i++)
					stars +=' *';
				return stars;
			}	
			
			function manageCheckBox(){
				$.each(checkedCheckBox, function(key,checkedId) {
					$('#'+checkedId).prop('checked', true);
				}); 

				$.each(unCheckedCheckBox, function(key,uncheckedId) {
					$('#'+uncheckedId).prop('checked', false);
				}); 
			}
            
        </script>    
        
        
</body>

</html>