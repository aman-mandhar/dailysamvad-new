# Dashboard permission matrix

| Workspace | Access boundary | Data scope |
|---|---|---|
| Super Admin | `manage permissions` | all authorized platform/content metrics |
| Admin | `manage users`, excluding `manage permissions` | administrative and editorial scope |
| Editor | `review posts` + `view all posts` | all permitted editorial posts |
| Reviewer | `review posts` + `view assigned posts`, without all-post access | assigned posts only |
| Reporter | `view own posts`, without review/all-post access | authored posts only |
| SEO | `view seo` or `manage seo` | posts visible to the account, SEO fields only |
| Media | `view media` or `manage media` | `ContentAccess::scopeMedia()` |
| Analytics | `view all analytics`, `view editorial analytics`, or `view own analytics` | verified views/visits within permission scope |
| Contributor | `create posts` + `view own posts`, without review access | authored posts only |

Permissions can overlap intentionally: for example, a reporter with `view own analytics` may access the analytics workspace, while their metrics remain owned-post scoped. Subscribers have no `access admin panel` permission.
