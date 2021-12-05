davekok/kernel
================================================================================

A PHP application kernel.

This is a proof of concept component. Not ready for production use. Currently it uses the stream_select function which does not scale very well.

If all the interfaces check out. A switch will be made to a more useful async IO backend.

Design
--------------------------------------------------------------------------------

Implementing asynchronous input/output without a kernel that schedules and controls the application is rather hard. In this application kernel component an effort is made to make it as easy as possible. However, a asynchronous call-stack is avoided. Instead actions must be planned. And the call-stack gets used more like a log in which is temporarily remembered what has been done. But holds no information about what should be done next or where to continue next. Hopefully keeping log and plan seperated will make it easier to understand. But more importantly does not lock processes to a CPU. Making it difficult to transfer state. Serializing and deserializing a call-stack can only be done, reliably, by giving every function a unique ID and refering to them by this ID in the call-stack independent of memory location and also giving every return point (after a function call) a unique ID. Simply using the offset of the function from the beginning of the program would not work reliably as this may change with the next version. Certain optimizations may also be more difficult like inlining functions.

Possible problems with fibers are:
- What to do when an application needs upgrading and fibers are still active? Wait, abort, avoid long running fibers?
- What to do for high availability scenario's, where a crash should not cause disruption or loss of state?
- How to monitor fibers? What if one gets stuck? How to detect and resolve? Kill the entire process? Requiring some protocol to manage fibers?

Hopefully, using activities will make it easier to replicate state among multiple processes. If one crashes another takes over. By monitoring the replication should make it easier to debug and detect stuck activities and remove them. New versions can be started without killing the old ones first. And existing activities should be transferable from process to process.

Lets hope it pans out that way.

Activities
--------------------------------------------------------------------------------

What should be done next is stored in an activity. Each activity holds zero or more actions, an activity with zero actions is considered inactive. Activities may be looped. Activities may fork and spawn new ones.

An action acts on an actionable. Basically anything can be an actionable, like a sockets, files or other activities. Actionables define which actions they support through interfaces. Actions provide an execute function to perform these actions. Actions requiring synchronisation or interaction will cause the kernel to hold that activity until a corresponding event has been received.
