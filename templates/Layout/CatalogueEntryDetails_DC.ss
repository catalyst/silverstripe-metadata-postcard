<div class="row">
    <div class="span12">
        <% include Breadcrumbs %>
        <div id="main" role="main">
            <% if $Top.ErrorMessage %>
                <div class="errorMessage">$ErrorMessage</div>
            <% else_if $MDTitle %>
                <h2>$MDTitle</h2>
                <div class="result">
                    <div class="catalogueBtns">
                        <a class="catalogueBtn" href="{$Top.Link('xml')}?id=$MDIdentifier" rel="noreferrer" target="_blank">Download Metadata XML</a>
                    </div>
                    <table class="catalougeTable">
                        <% loop $Rows %>
                            <tr>
                                <td style="width:27%"><strong>$Label:</strong></td>
                                <td>
                                    <% if $Value.Count %>
                                        <% loop $Value %>
                                            <% if $Label %><strong>$Label:</strong><% end_if %>
                                            $Value<% if not $Last %><br><% end_if %>
                                        <% end_loop %>
                                    <% else %>
                                        $Value
                                    <% end_if %>
                                </td>
                            </tr>
                        <% end_loop %>
                    </table>
                </div>
            <% else %>
                <div class="noRecords">Sorry, but no details were found for a record with the specified identifier.</div>
            <% end_if %>
            <div class='purpose'>
                <a id="catalogueBtn" class="catalogueBtn" href="#" onClick="javascript:history.back(); return false;">&#8249; Back</a>
            </div>
            <script>
                // If only 1 item in the history (the current page) then hide the back button as the user must
                // have come here directly (from bookmark) or opened this window in a new tab.
                if (history.length == 1) {
                    document.getElementById('catalogueBtn').style.display = 'none';
                }
            </script>
        </div>
    </div>
</div>
