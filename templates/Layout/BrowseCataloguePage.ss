<div class="row">
    <div class="<% if $HelpBoxTitle || $AddBoxTitle %>span9<% else %>span12<% end_if %>">
        <% include Breadcrumbs %>
        <div id="main" role="main">

            <h1 class="catalogueSearchTitle page-header">$Title</h1>

            <% if $Content %>
                <div class="catalogueContent">
                    $Content.RichLinks
                </div>
                <hr />
            <% end_if %>

            <% if $ErrorMessage %>
                <div class="errorMessage">$ErrorMessage</div>
            <% else %>

                <div class="catalogueSearch">
                    <form action="" method="GET" class="catalogueSearchForm">
                        <label>Search the catalogue</label>
                        <input type="text" name="searchKeyword" value="$SearchKeyword"/>
                        <input type="submit" class="searchButton" value="Search" />
                        <a class="clearSearch" href="$Link">Clear</a>
                    </form>
                </div>

                <% if $SearchKeyword %>
                    <h3>Search results for "$SearchKeyword"...</h3>
                <% end_if %>

                <% if $records %>

                    <!-- Top pagination -->
                    <div class='cataloguePagination'>
                        <% include CataloguePagination %>
                    </div>

                    <!-- Then the search / browse results -->
                    <div class='catalogueResults'>
                        <% loop $records %>
                        <div class="catalogueRecord">
                            <h3>
                                <% if $fileIdentifier %>
                                    <a href="$Up.Link(details)?id=$fileIdentifier" title="View details">
                                        <% if $MDTitle %>$MDTitle<% else %>(not available)<% end_if %>
                                    </a>
                                <% else %>
                                    <% if $MDTitle %>$MDTitle<% else %>(not available)<% end_if %>
                                <% end_if %>
                            </h3>
                            <p>
                                <% if $MDAbstract %>
                                    $MDAbstract.LimitCharacters(200)
                                <% else %>
                                    (not available)
                                <% end_if %>
                            </p>
                        </div>
                        <% end_loop %>
                    </div>
                    <!-- Bottom pagination -->
                    <div class='cataloguePagination'>
                        <% include CataloguePagination %>
                    </div>
                <% else %>
                    <div class="noRecordsMessage">Sorry, there are no records matching the entered keywords.</div>
                <% end_if %>
            <% end_if %>
            <!-- Normal page things -->
            <% include RelatedPages %>
            $CommentsForm
            <% include PrintShare %>
        </div>
        <% include LastEdited %>
    </div>
    <% if $HelpBoxTitle || $AddBoxTitle %>
        <aside class="span3">
            <% if $HelpBoxTitle %>
                <div class="sidebox">
                    <h3 style="margin-top:0;">$HelpBoxTitle</h3>
                    $HelpBoxMessage
                </div>
            <% end_if %>
            <% if $AddBoxTitle %>
                <div class="sidebox">
                    <h3 style="margin-top:0;">$AddBoxTitle</h3>
                    $AddBoxMessage
                    <a href="$AddCataloguePage.Link" target="_blank">Add a record...</a>
                </div>
            <% end_if %>
        </aside>
    <% end_if %>
</div>
