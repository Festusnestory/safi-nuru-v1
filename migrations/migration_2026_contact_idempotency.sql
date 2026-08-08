-- Exactly-once public inquiry submission for existing Nuru installations.
-- Apply once after confirming the column does not already exist.
ALTER TABLE public_inquiries
    ADD COLUMN submission_token CHAR(48) NULL AFTER source_page,
    ADD UNIQUE KEY uq_public_inquiries_submission_token (submission_token);
