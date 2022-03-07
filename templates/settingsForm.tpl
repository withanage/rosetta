<script>
	$(function () {ldelim}
		// Attach the form handler.
		$('#rosettaSettingsForm').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>
<form class="pkp_form" method="post" id="rosettaSettingsForm"
	  action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" plugin="RosettaExportPlugin" category="importexport" verb="settings" save="true"}">
    {fbvFormArea id="rosettaSettingsFormArea"}
		<p class="pkp_help">{translate key="plugins.importexport.rosetta.description"}</p>
    {/fbvFormArea}
    {fbvFormSection}
    {fbvElement type="text" disabled="true" required="false" id="rosettaHost" value=$rosettaHost label="plugins.importexport.rosetta.rosettaHost" maxlength="50" size=$fbvStyles.size.SMALL}
    {fbvElement type="text" disabled="true" required="false" id="subDirectoryName" value=$subDirectoryName label="plugins.importexport.rosetta.subDirectoryName" maxlength="50" size=$fbvStyles.size.SMALL}
    {fbvElement type="text" disabled="true" required="false"  id="rosettaProducerId" value=$rosettaProducerId label="plugins.importexport.rosetta.rosettaProducerId" maxlength="10" size=$fbvStyles.size.SMALL}
    {fbvElement type="text" disabled="true" required="false"  id="rosettaMaterialFlowId" value=$rosettaMaterialFlowId label="plugins.importexport.rosetta.rosettaMaterialFlowId" maxlength="10" size=$fbvStyles.size.SMALL}
    {/fbvFormSection}
    {fbvFormSection}
		<span class="instruct">{translate key="plugins.importexport.rosetta.rosettaHostInstructions"}</span>
    {fbvElement type="text" disabled="true" required="false" id="rosettaUsername" value=$rosettaUsername label="plugins.importexport.rosetta.rosettaUsername" maxlength="24" size=$fbvStyles.size.SMALL}
    {fbvElement type="text" disabled="true" required="false" password="true" id="rosettaPassword" value=$rosettaPassword label="plugins.importexport.rosetta.rosettaPassword" maxlength="24" size=$fbvStyles.size.SMALL}
    {/fbvFormSection}

</form>
