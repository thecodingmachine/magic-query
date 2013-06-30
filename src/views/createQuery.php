<?php
/* @var $this Mouf\Database\QueryWriter\Controllers\SelectController */
?>
<h1>Create a new SQL query</h1>

<form action="doCreateQuery" method="post">
	<div class="control-group">
		<label class="control-label">Instance name:</label>
		<div class="controls">
			<input type="text" name="name" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" />
		</div>
	</div>
	

	<label class="control-label">SQL query:</label>
	<textarea rows=10 name="sql" class="span10"><?php echo plainstring_to_htmlprotected($this->sql) ?></textarea>
	<div class="control-group">
		<div class="controls">
			<button name="action" value="parse" type="submit" class="btn btn-danger">Create SQL query object</button>
		</div>
	</div>
		
</form>