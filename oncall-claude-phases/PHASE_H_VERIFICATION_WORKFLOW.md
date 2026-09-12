# PHASE H — Identity and Credential Verification Workflow

Goal: marketplace users submit verification; web verification staff review it.

Users/mobile may submit, upload authorized documents, see status, and provide additional information. They may never approve themselves.

Web verifier may review, approve, reject, request more information, and revoke where authorized.

Support architecture for identity, mobile, email, driver's license, professional license, provider credentials, and admin verification. Only show badges backed by approved data.

Protect sensitive files and never expose storage paths publicly.

Audit reviewer, verification type, old/new status, timestamp, and notes/reason.

Test user/provider cannot self-approve, verifier can review, unrelated roles cannot approve unless explicitly permitted.

Stop when verification is a secure web-reviewed workflow.
