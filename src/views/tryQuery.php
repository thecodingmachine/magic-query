<?php
/* @var $this Mouf\Database\QueryWriter\Controllers\SelectController */
?>
<h1>Test your query</h1>

<pre id="mainQuery"><code><?php echo $this->sql; ?></code></pre>

<form action="" class="form-horizontal" id="parametersform">
	<input type="hidden" name="name" value="<?php echo plainstring_to_htmlprotected($this->instanceName); ?>" />

<?php if (!empty($this->parameters)): ?>
<div class="row">
	<div class="span6">
		<h2>Configure parameters</h2>
		
		<?php foreach ($this->parameters as $parameter) { ?>
			<div class="control-group">
				<label class="control-label"><?php echo plainstring_to_htmlprotected($parameter); ?>: </label>
				<div class="controls">
					<input type="text" name="parameters[<?php echo plainstring_to_htmlprotected($parameter); ?>]" value="" />
				</div>
			</div>
		<?php } ?>
	</div>
	<div class="span6">
		<h2>Query with parameters</h2>
		<pre><code id="createdSql"></code></pre>
	</div>
</div>

<script type="text/javascript">
function updateQuery() {
	var jqxhr = $.ajax( "getParameterizedQuery?"+$("#parametersform").serialize() )
	.done(function(data) { 
		$("#createdSql").text(data);
		$('code#createdSql').each(function(i, e) {hljs.highlightBlock(e)});
	})
	.fail(function(jqXHR, textStatus, errorThrown) { 
		addMessage("<pre>"+textStatus+":"+errorThrown+"</pre>", "error"); 
	});
}

$(document).ready(function() {
	$("#parametersform input").keyup(function() {
		updateQuery();
	});
	updateQuery();

	$('pre#mainQuery code').each(function(i, e) {hljs.highlightBlock(e)});
});
</script>

<?php endif; ?>

<div class="form-actions">
	<a href=".?name=<?php echo urlencode($this->instanceName) ?>" class="btn">&lt; Edit Query</a>
	<button type="submit" class="btn btn-primary" id="runQueryButton">Run Query</button>
	<a href="<?php echo ROOT_URL ?>ajaxinstance/?name=<?php echo urlencode($this->instanceName) ?>" class="btn">View instance &gt;</a>
</div>

</form>


<div id="resultsContainer"></div>

<script type="text/javascript">
$(document).ready(function() {
	$("#parametersform").submit(function(e) {
		e.preventDefault();
		$("#resultsContainer").empty();
		var grid = $("<div></div>").appendTo("#resultsContainer");
		grid.evolugrid({
	        url: MoufInstanceManager.rootUrl+"parseselect/runQuery?"+$("#parametersform").serialize(),
	        limit  : 50,
	        infiniteScroll: true
	    });
	});
	
	/*$("#runQueryButton").click(function() {
		$("#parametersform").trigger("submit");
	});*/
	
});
</script>