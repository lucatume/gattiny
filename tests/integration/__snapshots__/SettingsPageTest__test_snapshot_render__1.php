<?php return '<form action=\'options.php\' method=\'post\' class="gattiny">

	<h2>Gattiny Settings</h2>

	<input type=\'hidden\' name=\'option_page\' value=\'gattiny\' /><input type="hidden" name="action" value="update" /><input type="hidden" id="_wpnonce" name="_wpnonce" value="eb228622ce" /><input type="hidden" name="_wp_http_referer" value="" /><h2>Image sizes</h2>
<p>Converting animated images,  while preserving their animations, could potentially take a long time to finish. If you are experiencing an unresponsive UI or timeout messages use the settings below to tweak Gattiny behaviour.</p><p>Images will never be upscaled to larger format; Gattiny will not change the default WordPress behaviour.</p><table class="form-table"><tr><th scope="row">Decide how each image size conversion should be handled</th><td><fieldset class="interline">
	<legend>Image size <code>thumbnail</code> - (w 150px, h 150px, cropping: yes)</legend>
			<label for="gattiny-imageSizes[thumbnail]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[thumbnail]"
		 checked=\'checked\'		   value="convert-animated"
	>
	Convert preserving animations (<span class=\'load-low\'> - fast conversion</span>)</label>			<label for="gattiny-imageSizes[thumbnail]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[thumbnail]"
				   value="convert-still"
	>
	Convert removing animations (default WordPress behaviour)</label>			<label for="gattiny-imageSizes[thumbnail]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[thumbnail]"
				   value="do-not-convert"
	>
	Do not convert</label>	</fieldset>
<fieldset class="interline">
	<legend>Image size <code>medium</code> - (w 300px, h 300px, cropping: no)</legend>
			<label for="gattiny-imageSizes[medium]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[medium]"
		 checked=\'checked\'		   value="convert-animated"
	>
	Convert preserving animations (<span class=\'load-medium\'> - up to 2s to convert</span>)</label>			<label for="gattiny-imageSizes[medium]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[medium]"
				   value="convert-still"
	>
	Convert removing animations (default WordPress behaviour)</label>			<label for="gattiny-imageSizes[medium]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[medium]"
				   value="do-not-convert"
	>
	Do not convert</label>	</fieldset>
<fieldset class="interline">
	<legend>Image size <code>medium_large</code> - (w 768px, h 0px, cropping: no)</legend>
			<label for="gattiny-imageSizes[medium_large]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[medium_large]"
		 checked=\'checked\'		   value="convert-animated"
	>
	Convert preserving animations (<span class=\'load-medium\'> - up to 2s to convert</span>)</label>			<label for="gattiny-imageSizes[medium_large]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[medium_large]"
				   value="convert-still"
	>
	Convert removing animations (default WordPress behaviour)</label>			<label for="gattiny-imageSizes[medium_large]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[medium_large]"
				   value="do-not-convert"
	>
	Do not convert</label>	</fieldset>
<fieldset class="interline">
	<legend>Image size <code>large</code> - (w 1024px, h 1024px, cropping: no)</legend>
			<label for="gattiny-imageSizes[large]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[large]"
				   value="convert-animated"
	>
	Convert preserving animations (<span class=\'load-high\'> - takes time to convert</span>)</label>			<label for="gattiny-imageSizes[large]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[large]"
		 checked=\'checked\'		   value="convert-still"
	>
	Convert removing animations (default WordPress behaviour)</label>			<label for="gattiny-imageSizes[large]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[large]"
				   value="do-not-convert"
	>
	Do not convert</label>	</fieldset>
<fieldset class="interline">
	<legend>Image size <code>twentyseventeen-featured-image</code> - (w 2000px, h 1200px, cropping: yes)</legend>
			<label for="gattiny-imageSizes[twentyseventeen-featured-image]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[twentyseventeen-featured-image]"
				   value="convert-animated"
	>
	Convert preserving animations (<span class=\'load-high\'> - takes time to convert</span>)</label>			<label for="gattiny-imageSizes[twentyseventeen-featured-image]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[twentyseventeen-featured-image]"
		 checked=\'checked\'		   value="convert-still"
	>
	Convert removing animations (default WordPress behaviour)</label>			<label for="gattiny-imageSizes[twentyseventeen-featured-image]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[twentyseventeen-featured-image]"
				   value="do-not-convert"
	>
	Do not convert</label>	</fieldset>
<fieldset class="interline">
	<legend>Image size <code>twentyseventeen-thumbnail-avatar</code> - (w 100px, h 100px, cropping: yes)</legend>
			<label for="gattiny-imageSizes[twentyseventeen-thumbnail-avatar]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[twentyseventeen-thumbnail-avatar]"
		 checked=\'checked\'		   value="convert-animated"
	>
	Convert preserving animations (<span class=\'load-low\'> - fast conversion</span>)</label>			<label for="gattiny-imageSizes[twentyseventeen-thumbnail-avatar]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[twentyseventeen-thumbnail-avatar]"
				   value="convert-still"
	>
	Convert removing animations (default WordPress behaviour)</label>			<label for="gattiny-imageSizes[twentyseventeen-thumbnail-avatar]" class="">
	<input type="radio"
		   class=""
		   name="gattiny-imageSizes[twentyseventeen-thumbnail-avatar]"
				   value="do-not-convert"
	>
	Do not convert</label>	</fieldset>
</td></tr></table><p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  /></p>
</form>
';
