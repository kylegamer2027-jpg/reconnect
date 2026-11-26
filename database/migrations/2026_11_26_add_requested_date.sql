ALTER TABLE document_requests
    ADD COLUMN requested_date DATE NULL AFTER quantity;
