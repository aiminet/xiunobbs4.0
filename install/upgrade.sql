ALTER TABLE bbs_session DROP KEY uid;
ALTER TABLE bbs_session ADD KEY uid_last_date(uid, last_date);