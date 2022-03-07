{include file="common/header.tpl" pageTitle="plugins.importexport.rosetta.displayName"}
<script>
	// Attach the JS file tab handler.
	$(function () {ldelim}
		$('#rosettaExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
        {rdelim});
</script>
<div id="rosettaExportTabs">
	<ul>
		<li><a href="#rosetta-settings-tab">{translate key="plugins.importexport.rosetta.settings"}</a></li>
	</ul>
	<div id="rosetta-settings-tab">
        {capture assign=rosettaSettingsGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.settings.plugins.settingsPluginGridHandler" op="manage" plugin="RosettaExportPlugin" category="importexport" verb="settings" escape=false}{/capture}
        {load_url_in_div id="rosettaSettingsGridContainer" url=$rosettaSettingsGridUrl}
	</div>
</div>
{include file="common/footer.tpl"}
