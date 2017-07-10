<div class="row">
    <div class="span12">
        <% include Breadcrumbs %>
        <div id="main" role="main">
            <% if $Top.ErrorMessage %>
                <div class="errorMessage">$ErrorMessage</div>
            <% else_if $fileIdentifier %>
                <h2>$MDTitle</h2>
                <div class="result">

                    <div class="catalogueBtns">
                        <% if $CIOnlineResources %>
                            <% loop $CIOnlineResources %>
                                <% if $CIOnlineProtocol == 'WWW:LINK-1.0-http--metadata-URL' %>
                                    <% if $CIOnlineLinkage %>
                                        <a class="catalogueBtn" href="$CIOnlineLinkage" style="background-image: none;" rel="noreferrer" target="_blank">View full metadata</a>
                                    <% else %>
                                        Not Available
                                    <% end_if %>
                                <% end_if %>
                            <% end_loop %>
                        <% end_if %>
                        <a class="catalogueBtn" href="{$Top.Link}xml/?id=$fileIdentifier" rel="noreferrer" target="_blank">Download Metadata XML</a>
                    </div>

                    <table class="catalougeTable">
                        <% loop $MDCitationDates %>
                        <tr>
                            <td style="width:27%"><strong>Date of $MDDateType:</strong></td>
                            <td>$Top.DateFormatNice($MDDateTime)</td>
                        </tr>
                        <% end_loop %>
                        <tr>
                            <td><strong>Category:</strong></td>
                            <td><% loop $MDTopicCategory %> $Value<br/> <% end_loop %></td>
                        </tr>
                        <tr>
                            <td><strong>Description:</strong></td>
                            <td>
                                <% if $MDAbstract %>
                                    $MDAbstract
                                <% else %>
                                    Not Available
                                <% end_if %>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Geographic Description:</strong></td>
                            <td>
                                <% if $MDGeographicDiscription %>
                                    $MDGeographicDiscription
                                <% else %>
                                    Not Available
                                <% end_if %>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Coordinates:</strong></td>
                            <td>
                                <strong>North:</strong> <% if $MDNorthBound %>$MDNorthBound<% else %>Not Available<% end_if %>,
                                <strong>West:</strong> <% if $MDWestBound %>$MDWestBound<% else %>Not Available<% end_if %>,
                                <strong>East:</strong> <% if $MDEastBound %>$MDEastBound<% else %>Not Available<% end_if %>,
                                <strong>South:</strong> <% if $MDSouthBound %>$MDSouthBound<% else %>Not Available<% end_if %>
                            </td>
                        </tr>
                        <% if $MDContacts %>
                        <tr>
                            <td>
                                <strong>Contact:</strong>
                                <p>To get additional data or seek further information</p>
                            </td>
                            <% loop $PointOfContact %>
                            <td>
                                <strong>Name:</strong> <% if $MDIndividualName %>$MDIndividualName<% else %>Not Available <% end_if %><br/>
                                <strong>Organisation:</strong> <% if $MDOrganisationName %>$MDOrganisationName<% else %>Not Available <% end_if %><br/>
                                <strong>Position:</strong> <% if $MDPositionName %>$MDPositionName<% else %>Not Available <% end_if %><br/>
                                <strong>Phone:</strong>
                                <% if $MDVoice %>
                                    <% loop $MDVoice %>
                                    $Value <br/>
                                    <% end_loop %>
                                <% else %>
                                    Not Available <br/>
                                <% end_if %>
                            <% end_loop %>
                        </tr>
                        <% end_if %>
                        <% if $MCPMDCreativeCommons %>
                        <tr>
                            <td>
                                <% if $First %>
                                    <strong>Licence:</strong>
                                <% else %>
                                <% end_if %>
                            </td>
                            <td>
                                <% loop $MCPMDCreativeCommons %>
                                    <% if $imageLink %>
                                        <% if $licenseLink %>
                                            <a href='$licenseLink' target='licence' style="background:none;"><img style="float:left;" src='$imageLink'/></a>
                                        <% else %>
                                            <img style="float:left;" src='$imageLink'/>
                                        <% end_if %>
                                    <% end_if %>
                                    <% if $useLimitation %>
                                        <span style="float:left;">$useLimitation </span>
                                    <% end_if %>
                                    <% if $useLimitation %>
                                        <br/>
                                        <a href='copyright-attributing' target='licence'>Attribution Information</a>
                                        <br/>
                                        <br/>
                                    <% end_if %>
                                <% end_loop %>
                            </td>
                        </tr>
                        <% else %>
                        <tr>
                            <td>
                                <strong>Licence:</strong>
                            </td>
                            <td>
                                Not Avaliable</p>
                            </td>
                        </tr>
                        <% end_if %>
                        <tr>
                            <td>
                                <strong>Web address (URL):</strong><br/>
                                Click on link to download
                            </td>
                            <td>
                                <% if $CIOnlineResources %>
                                    <% loop $CIOnlineResources %>
                                        <% if $CIOnlineProtocol == 'WWW:LINK-1.0-http--downloaddata' %>
                                            <% if $CIOnlineLinkage %>
                                                <% if $CIOnlineName %>
                                                    <a href="$CIOnlineLinkage" rel="noreferrer" target="_blank">$CIOnlineName</a><br/>
                                                    $CIOnlineDescription
                                                <% else %>
                                                    <a href="$CIOnlineLinkage" rel="noreferrer" target="_blank">$CIOnlineDescription</a>
                                                <% end_if %>
                                                <br/><br/>
                                            <% else %>
                                                Not Available
                                            <% end_if %>
                                        <% end_if %>
                                    <% end_loop %>
                                <% else %>
                                    Not Available
                                <% end_if %>
                            </td>
                        </tr>
                        <% if $MDKeywords %>
                        <tr>
                            <td><strong>Keywords:</strong></td>
                            <td>
                                <% loop $MDKeywords %>
                                    $Value<br />
                                <% end_loop %>
                            </td>
                        </tr>
                        <% end_if %>
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
