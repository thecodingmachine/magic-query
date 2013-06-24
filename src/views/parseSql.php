<?php
/* @var $this Mouf\Database\QueryWriter\Controllers\SelectController */
?>
<h1>Parse SQL</h1>

<form action="parse">
	<input type="hidden" name="name" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" />
	<label class="control-label">SQL query:</label>
	<textarea rows=10 name="sql" class="span10"><?php echo plainstring_to_htmlprotected($this->sql) ?></textarea>
	<div class="control-group">
		<div class="controls">
			<button name="action" value="parse" type="submit" class="btn btn-danger">Create object from query</button>
		</div>
	</div>
		
</form>