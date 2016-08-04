<?php
namespace AgenDAV\Data\Helper;

use AgenDAV\Data\Share;

class SharesDiffTest extends \PHPUnit_Framework_TestCase
{
    public function testEmpty()
    {
        $diff = new SharesDiff([]);
        $diff->decide([]);

        $this->assertCount(0, $diff->getKeptShares(), '[] + [] != []');
        $this->assertCount(0, $diff->getMarkedForRemoval(), '[] - [] != []');
    }

    public function testAdd()
    {
        $diff = new SharesDiff([]);
        $input = $this->generateShares(4);
        $diff->decide($input);

        $this->assertCount(4, $diff->getKeptShares(), '[] + [a,b,c,d] != [a,b,c,d]');
        $this->assertCount(0, $diff->getMarkedForRemoval(), '[] - [] != []');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            '[] + [a,b,c,d] result objects do not match [a,b,c,d]'
        );
    }

    /**
     * Test what happens when we have N existing shares and we add one more
     */
    public function testAddNewShare()
    {
        $input = $this->generateShares(5);
        $existing = array_slice($input, 0, 4);
        shuffle($existing);
        $diff = new SharesDiff($existing);
        $diff->decide($input);

        $this->assertCount(5, $diff->getKeptShares(), '[a,b,c,d] + [a,b,c,d,e] != [a,b,c,d,e]');
        $this->assertCount(0, $diff->getMarkedForRemoval(), '[a,b,c,d] - [] != []');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            '[a,b,c,d] + [a,b,c,d,e] result objects do not match [a,b,c,d,e]'
        );
    }

    /**
     * Test what happens when we have N existing shares and we remove one from them
     */
    public function testRemoveOneShare()
    {
        $existing = $this->generateShares(5);
        $input = array_slice($existing, 0, 4);
        shuffle($input);
        $diff = new SharesDiff($existing);
        $diff->decide($input);

        $this->assertCount(4, $diff->getKeptShares(), '[a,b,c,d,e] - [e] != [a,b,c,d]');
        $this->assertCount(1, $diff->getMarkedForRemoval(), '[a,b,c,d,e] diff [a,b,c,d] != [e]');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            '[a,b,c,d,e] - [e] result objects do not match [a,b,c,d]'
        );

        $this->assertEquals(
            $existing[4],
            current($diff->getMarkedForRemoval()),
            'Last element was not removed'
        );
    }

    /**
     * Test what happens when we have N existing shares and we alter one of them.
     *
     * It should not remove any of the existing shares but modify the original one
     */
    public function testAlterOneShare()
    {
        $existing = $this->generateShares(3);
        foreach ($existing as $share) {
            $input[] = clone $share;
        }

        $input[0]->setWritePermission(!$input[0]->isWritable());
        shuffle($input);

        $diff = new SharesDiff($existing);
        $diff->decide($input);

        $this->assertCount(3, $diff->getKeptShares(), '[a,b,c] + [a,b,c\'] != [a,b,c\']');
        $this->assertCount(0, $diff->getMarkedForRemoval(), '[a,b,c] diff [a,b,c\'] != []');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            '[a,b,c] - [c] + [c\'] result does not match [a,b,c\']'
        );
    }

    /**
     * Test what happens when we have N existing shares and we:
     *  1) Add a new share
     *  2) Alter an existing share
     *  3) Remove an existing share
     */
    public function testAddRemoveAndAlter()
    {
        $existing = $this->generateShares(4);
        foreach ($existing as $share) {
            $input[] = clone $share;
        }

        unset($input[0]); // Remove the first one
        $input[1]->setWritePermission(!$input[1]->isWritable()); // Alter the second one
        $new_inputs = $this->generateShares(1, 4);
        $input[] = $new_inputs[0];
        shuffle($input);

        $diff = new SharesDiff($existing);
        $diff->decide($input);

        $this->assertCount(4, $diff->getKeptShares(), '[a,b,c,d] - [a] + [e] + [b*] != [b*,c,d,e]');
        $this->assertCount(1, $diff->getMarkedForRemoval(), 'One element should have been marked for removal');

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getKeptShares(), $input),
            'a,b,c,d] - [a] + [e] + [b*] != [b*,c,d,e]'
        );

        $this->assertTrue(
            $this->assertAllObjectsAreEqual($diff->getMarkedForRemoval(), [ $existing[0] ]),
            'Element marked for removal was not returned'
        );
    }


    /**
     * Generates N shares
     *
     * @param int $n Number of shares
     * @param int $start Nunber to start
     * @return \AgenDAV\Data\Share[]
     */
    protected function generateShares($n, $start = 0)
    {
        $with = '/with';
        $result = [];

        $total = $start + $n;
        for($i=$start;$i<$total;$i++) {
            $share = new Share;
            $share->setWith($with . '-' . $i);
            $share->setCalendar('/calendar');
            $share->setOwner('/me');
            $share->setWritePermission(rand(0, 1) == 1);

            $result[] = $share;
        }

        return $result;
    }

    /**
     * @return bool
     */
    protected function assertAllObjectsAreEqual(array $first, array $second)
    {
        if (count($first) != count($second)) {
            return false;
        }

        $diff = array_udiff($second, $first, function(Share $s1, Share $s2) {
            if ($s1->getWith() == $s2->getWith() && $s1->isWritable() === $s2->isWritable()) {
                return 0;
            }

            $id_1 = substr($s1->getWith(), 5); // 'with-'
            $id_2 = substr($s2->getWith(), 5); // 'with-'

            return (int)$id_1 - (int)$id_2;
        });

        if (count($diff) != 0) {
            return false;
        }

        return true;
    }
}
