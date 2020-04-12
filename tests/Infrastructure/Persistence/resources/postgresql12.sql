CREATE TABLE command (
    id UUID NOT NULL,
    name VARCHAR(255) NOT NULL,
    payload JSONB NOT NULL,
    result JSONB DEFAULT NULL,
    status VARCHAR(63) NOT NULL,
    changed_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    count INT NOT NULL,
    next_attempt_at TIMESTAMP(0) WITH TIME ZONE NOT NULL,
    PRIMARY KEY(id)
);
COMMENT ON COLUMN command.id IS '(DC2Type:uuid)';
COMMENT ON COLUMN command.status IS '(DC2Type:command_status)';
COMMENT ON COLUMN command.changed_at IS '(DC2Type:datetimetz_immutable)';
COMMENT ON COLUMN command.next_attempt_at IS '(DC2Type:datetimetz_immutable)';
