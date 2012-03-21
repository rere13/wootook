<?php

class Wootook_Core_Database_Sql_Update
    extends Wootook_Core_Database_Sql_DmlFilterableQuery
{
    const SET    = 'SET';
    const INTO   = 'INTO';

    protected function _init($tableName = null)
    {
        if ($tableName !== null) {
            $this->into($tableName);
        }

        return $this;
    }

    public function reset($part = null)
    {
        if ($part === null) {
            $this->_parts = array(
                self::INTO   => array(),
                self::SET    => array(),
                self::WHERE   => array(),
                self::LIMIT   => null,
                self::OFFSET  => null,
                );
        } else if (isset($this->_parts[$part])) {
            $this->_parts[$part] = array();
        }

        return $this;
    }

    public function set($column, $value = null)
    {
        if (!is_array($column)) {
            $column = array($column => $value);
        }

        foreach ($column as $field => $value) {
            if ($field instanceof Wootook_Core_Database_Sql_Placeholder_Placeholder) {
                $this->_placeholders[] = $field;
            }

            $this->_parts[self::SET][] = array(
                'value' => $value,
                'field' => $field
                );
        }

        return $this;
    }

    public function into($table, $schema = null)
    {
        $this->_parts[self::INTO] = array(
            'table'  => $table,
            'schema' => $schema,
            );

        return $this;
    }

    public function __toString()
    {
        return $this->render();
    }

    public function toString($part = null)
    {
        if ($part === null) {
            return $this->render();
        }

        switch ($part) {
        case self::SET:
            return $this->renderSet();
            break;
        case self::INTO:
            return $this->renderInto();
            break;
        }

        return null;
    }

    public function renderSet()
    {
        $values = array();
        $fields = array();
        foreach ($this->_parts[self::SET] as $field) {
            if ($field['value'] instanceof Wootook_Core_Database_Sql_Placeholder_Placeholder) {
                $values[] = $field['value']->toString();
                $fields[] = $this->_connection->quoteIdentifier($field['field']);
            } else {
                $values[] = $this->_connection->quote($field['value']);
                $fields[] = $this->_connection->quoteIdentifier($field['field']);
            }
        }

        if (!empty($fields)) {
            return ' (' . implode(', ', $fields). ")\nVALUES (" . implode(", ", $values) . ')';
        }
    }

    public function renderInto()
    {
        if ($this->_parts[self::INTO]['schema'] !== null) {
           $output = "{$this->_connection->quoteIdentifier($this->_parts[self::INTO]['schema'])}.{$this->_connection->quoteIdentifier($this->_parts[self::INTO]['table'])}";
        } else {
           $output = "{$this->_connection->quoteIdentifier($this->_parts[self::INTO]['table'])}";
        }

        return "UPDATE " . $output;
    }

    public function render()
    {
        return implode('', array(
            $this->renderInto(),
            $this->renderSet(),
            $this->renderWhere(),
            $this->renderLimit()
            ));
    }
}
