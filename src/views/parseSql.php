<?php
/* @var $this Mouf\Database\QueryWriter\Controllers\SelectController */
?>
<h1>Parse SQL</h1>

<?php if ($this->parseError) { ?>
	<div class="alert">Unable to parse SQL query</div>
<?php } ?>

<form action="parse">
	<input type="hidden" name="name" value="<?php echo plainstring_to_htmlprotected($this->instanceName) ?>" />
	<label class="control-label">SQL query:</label>
	<div class="controls">
		<textarea rows=10 name="sql" class="span10"><?php echo plainstring_to_htmlprotected($this->sql) ?></textarea>
		<span class="help-block">You can use <strong>parameters</strong> using prepared statement notation. For instance: 
				<code>select * from users where country_id = :country_id</code></span>
	</div>			
	<div class="control-group">
		<div class="controls">
			<button name="action" value="parse" type="submit" class="btn btn-danger">Create object from query</button>
		</div>
	</div>
		
</form>