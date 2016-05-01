#include <stdio.h>
#include <stdlib.h>

#include "math-index.h"
#include "config.h"

enum dir_merge_ret
on_dir_merge(math_posting_t postings[MAX_MATH_PATHS], uint32_t n_postings,
             uint32_t level, void *args)
{
	return DIR_MERGE_STOP;
}

int main(int argc, char *argv[])
{
	const char tex[] = "\\qvar\\alpha+xy";
	struct tex_parse_ret parse_ret;

	math_index_t index = math_index_open("./tmp", MATH_INDEX_READ_ONLY);

	if (index == NULL) {
		printf("cannot open index.\n");
		return 1;
	}

	printf("search: `%s'\n", tex);
	parse_ret = tex_parse(tex, 0, false);

	if (parse_ret.code == PARSER_RETCODE_SUCC) {
		subpaths_print(&parse_ret.subpaths, stdout);
		printf("start merging...\n");
		math_index_dir_merge(index, DIR_MERGE_DEPTH_FIRST,
		                     &parse_ret.subpaths, &on_dir_merge, NULL);
		
		subpaths_release(&parse_ret.subpaths);
	} else {
		printf("parser error: %s\n", parse_ret.msg);
	}

	math_index_close(index);
	return 0;
}