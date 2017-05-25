<div class="row">
    <div class="<% if $HelpBoxTitle || $BrowseBoxTitle %>span9<% else %>span12<% end_if %>">
        <% include Breadcrumbs %>
        <div id="main" role="main">
            <h1 class="page-header">$Title</h1>
            $Content.RichLinks
            <% if $PreviouslyCreatedRecords %>
                <div>
                    Records added this session...<br />
                    <ul>
                        <% loop $PreviouslyCreatedRecords %>
                            <li><a href="$Link" target="_blank">$Link</a></li>
                        <% end_loop %>
                    </ul>
                </div>
                <br />
            <% end_if %>
            $MetadataEntryForm
            <% include RelatedPages %>
            $CommentsForm
            <% include PrintShare %>
        </div>
        <% include LastEdited %>
    </div>
    <% if $HelpBoxTitle || $BrowseBoxTitle %>
        <aside class="span3">
            <% if $HelpBoxTitle %>
                <div class="helpbox" style="background-color:#eee;padding:20px;margin-bottom:30px;">
                    <h3 style="margin-top:0;">$HelpBoxTitle</h3>
                    $HelpBoxMessage
                    <% loop $Curators %>
                        <a href="mailto:$Email">$Email</a>
                    <% end_loop %>
                </div>
            <% end_if %>
            <% if $BrowseBoxTitle %>
                <div class="helpbox" style="background-color:#eee;padding:20px;margin-bottom:30px;">
                    <h3 style="margin-top:0;">$BrowseBoxTitle</h3>
                    $BrowseBoxMessage
                    <a href="$BrowseCataloguePage.Link" target="_blank">Browse the catalogue...</a>
                </div>
            <% end_if %>
        </aside>
    <% end_if %>
</div>
