#include <getopt.h>
#include <stdlib.h>
#include <stdio.h>

#include "mhook/mhook.h"
#include "common/common.h"

#include "query.h"
#include "search.h"
#include "search-utils.h"

void print_res_item(struct rank_hit* hit, uint32_t cnt, void* arg_)
{
	char  *str;
	size_t str_sz;
	list   highlight_list;
	PTR_CAST(indices, struct indices, arg_);

	printf("page result#%u: doc#%u score=%.3f\n",
	        cnt + 1, hit->docID, hit->score);

	/* get URL */
	str = get_blob_string(indices->url_bi, hit->docID, 0, &str_sz);
	printf("URL: %s" "\n", str);
	free(str);

	{ /* print occur positions */
		int i;
		printf("occurs: ");
		for (i = 0; i < hit->n_occurs; i++)
			printf("%u ", hit->occurs[i].pos);
		printf("\n");
	}

	/* get document text */
	str = get_blob_string(indices->txt_bi, hit->docID, 1, &str_sz);

	/* prepare highlighter arguments */
	highlight_list = prepare_snippet(hit, str, str_sz);
	free(str);

	/* print snippet */
	snippet_hi_print(&highlight_list);
	printf("\n\n");

	/* free highlight list */
	snippet_free_highlight_list(&highlight_list);
}

void
print_res(struct indices *indices, ranked_results_t *rk_res, uint32_t page)
{
	struct rank_window wind;
	uint32_t i, from_page = page, to_page = page, tot_pages = 1;
	wind = rank_window_calc(rk_res, 0, DEFAULT_RES_PER_PAGE, &tot_pages);

	/* show all pages */
	if (page == 0) {
		from_page = 1;
		to_page = tot_pages;
	}

	for (i = from_page - 1; i < MIN(to_page, tot_pages); i++) {
		printf("page %u/%u, top result(s) from %u to %u:\n",
			   i + 1, tot_pages, wind.from + 1, wind.to);
		wind = rank_window_calc(rk_res, i, DEFAULT_RES_PER_PAGE, &tot_pages);
		rank_window_foreach(&wind, &print_res_item, indices);
	}
}

int main(int argc, char *argv[])
{
	struct query         qry;
	struct indices       indices;
	int                  opt;
	char                 index_path[1024];
	ranked_results_t     results;
	int                  page = 1;

	qry = query_new();

	while ((opt = getopt(argc, argv, "hi:m:t:p:")) != -1) {
		switch (opt) {
		case 'h':
			printf("DESCRIPTION:\n");
			printf("testcase of search function.\n");
			printf("\n");
			printf("USAGE:\n");
			printf("%s -h |"
			       " -i <index path> |"
			       " -t <text keyword> |"
			       " -m <math keyword> |"
			       " -p <page> |"
			       "\n", argv[0]);
			printf("\n");
			goto exit;

		case 'i':
			strcpy(index_path, optarg);
			break;

		case 'p':
			sscanf(optarg, "%u", &page);
			break;

		case 'm':
			query_push_keyword_str(&qry, optarg, QUERY_KEYWORD_TEX);
			break;

		case 't':
			query_push_keyword_str(&qry, optarg, QUERY_KEYWORD_TERM);
			break;

		default:
			printf("bad argument(s). \n");
			goto exit;
		}
	}

	if (index_path == NULL || qry.len == 0) {
		printf("not enough arguments.\n");
		goto exit;
	}

	printf("opening index at path: `%s' ...\n", index_path);
	if (indices_open(&indices, index_path, INDICES_OPEN_RD)) {
		printf("index open failed.\n");
		goto close;
	}

	query_print(qry, stdout);

	printf("caching index...\n");
	postlist_cache_set_limit(&indices.ci, 100 KB, 100 KB);
	indices_cache(&indices);

	/* search query */
	printf("running query...\n");
	results = indices_run_query(&indices, &qry);

	/* print ranked search results in pages */
	printf("showing results (page %u) ...\n", page);
	print_res(&indices, &results, page);

	/* free ranked results */
	free_ranked_results(&results);

close:
	printf("closing index...\n");
	indices_close(&indices);

exit:
	printf("existing...\n");
	query_delete(qry);

	mhook_print_unfree();
	return 0;
}
