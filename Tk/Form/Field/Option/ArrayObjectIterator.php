<?php
namespace Tk\Form\Field\Option;

use Tk\Form\Field\Option;

/**
 * Use this iterator to create an option list from
 * objects. The parameters that are to be accessed in the object
 * must be declared public.
 *
 * <?php
 *   $list = new ObjectArrayIterator(\App\Db\User::getMapper()->findAll(), 'name', 'id');
 * ?>
 *
 * @author Michael Mifsud <info@tropotek.com>
 * @see http://www.tropotek.com/
 * @license Copyright 2015 Michael Mifsud
 */
class ArrayObjectIterator extends ArrayIterator
{
    /**
     * @var string
     */
    protected $textParam = '';

    /**
     * @var string
     */
    protected $valueParam = '';

    /**
     * @var string
     */
    protected $disableParam = '';

    /**
     * @var string
     */
    protected $labelParam = '';

    /**
     * @var string
     */
    protected $selectedValue = '';

    /**
     * @var string
     */
    protected $selectedAppend = ' (Current)';

    /**
     * @var string
     */
    protected $selectedPrepend = '';


    /**
     *
     * @param array $list
     * @param string|callable $textParam
     * @param string|callable $valueParam
     * @param string $disableParam
     * @param string $labelParam
     */
    public function __construct($list = array(), $textParam = 'name', $valueParam = 'id', $disableParam = '', $labelParam = '')
    {
        if ($list instanceof \Tk\Db\Map\ArrayObject) {
            $list = $list->toArray();
        }
        parent::__construct($list);

        $this->textParam = $textParam;
        $this->valueParam = $valueParam;
        $this->disableParam = $disableParam;
        $this->labelParam = $labelParam;
    }

    /**
     * @param array $list
     * @param string|callable $textParam
     * @param string|callable $valueParam
     * @param string $disableParam
     * @param string $labelParam
     * @return ArrayObjectIterator
     */
    static function create($list = array(), $textParam = 'name', $valueParam = 'id', $disableParam = '', $labelParam = '')
    {
        return new self($list, $textParam, $valueParam, $disableParam, $labelParam);
    }

    /**
     * @param string $value
     * @return ArrayObjectIterator
     */
    public function setSelectedValue($value)
    {
        $this->selectedValue = $value;
        return $this;
    }

    /**
     * @param string $selectedAppend
     * @return ArrayObjectIterator
     */
    public function setSelectedAppend($selectedAppend)
    {
        $this->selectedAppend = $selectedAppend;
        return $this;
    }

    /**
     * @param string $selectedPrepend
     * @return ArrayObjectIterator
     */
    public function setSelectedPrepend($selectedPrepend)
    {
        $this->selectedPrepend = $selectedPrepend;
        return $this;
    }


    /**
     * Return the current element
     *
     * @see http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        $obj = $this->list[$this->getKey($this->idx)];
        $text = '';
        $value = '';
        $disabled = false;

        if ( is_callable($this->valueParam) ) {
            $value = call_user_func_array($this->valueParam, array($obj));
        } else if (property_exists($obj, $this->valueParam)) {
            $value = $obj->{$this->valueParam};
        }

        $pre = $app = '';
        if ($value == $this->selectedValue) {
            $pre = $this->selectedPrepend;
            $app = $this->selectedAppend;
        }
        if ( is_callable($this->textParam) ) {
            $text = call_user_func_array($this->textParam, array($obj));
        } else if (property_exists($obj, $this->textParam)) {
            $text = $pre . $obj->{$this->textParam} . $app;
        }

        if (property_exists($obj, $this->disableParam)) {
            $disabled = $obj->{$this->disableParam};
        }

        // Create the option object from the object supplied
        return new Option($text, $value, $disabled);
    }



}