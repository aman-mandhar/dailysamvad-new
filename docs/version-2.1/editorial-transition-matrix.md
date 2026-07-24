# Editorial transition matrix

| From | To | Action / permission | Result |
|---|---|---|---|
| draft, changes_requested | pending_review | submit / submit posts for review | submission metadata; editorial notification |
| pending_review | changes_requested | request corrections / request post corrections | required notes; author notified |
| pending_review | approved | approve / approve posts | approval metadata; author notified |
| pending_review | rejected | reject / reject posts | required reason; author notified |
| approved | scheduled | schedule / schedule posts | future time and actor |
| scheduled | scheduled | reschedule / schedule posts | replaces future time; new event |
| scheduled | approved | cancel schedule / schedule posts | clears schedule |
| approved, scheduled | published | publish / publish posts or trusted scheduler | publication metadata |
| published | archived | archive / archive posts | archive metadata; no deletion |
| rejected, archived | draft | restore / restore posts | explicit recovery |

All other transitions are rejected. Approval and publication are separate. Row locks serialize concurrent transitions.
