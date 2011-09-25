CREATE INDEX last_activity_idx ON sessions(last_activity);
ALTER TABLE sessions MODIFY user_agent VARCHAR(120); 
