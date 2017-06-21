<div class="paginationActions">
	<p>
		<% if pagination.isFirstPage %>
			<span class="cataloguePaginationPrev">Prev</span>
		<% else %>
			<a href="{$Link}?search=$SearchKeyword&page=$pagination.previousPage" class="cataloguePaginationPrevLink">&#8249; Prev</a>
		<% end_if %>

		<span class="cataloguePaginationPages">Page $pagination.currentPage of $pagination.totalPages</span>

		<% if pagination.isLastPage %>
			<span class="cataloguePaginationNext">Next</span>
		<% else %>
			<a href="{$Link}?search=$SearchKeyword&page=$pagination.nextPage" class="cataloguePaginationNextLink">Next &#8250;</a>
		<% end_if %>
	</p>
</div>
