davekok/stream
================================================================================

A stream abstraction library.

This is a proof of concept library. Not ready for production use. Currently it uses the stream_select function which does not scale very well.

If all the interfaces check out. A switch will be made to a more useful async IO backend.

Design
--------------------------------------------------------------------------------

The design is based on a layered looped cooperative threading model. Threads are thus build up from small loops. The small loops are entangled together and are also looped. These larger loops may be entangled together again forming even larger loops. Small loops are expressed in while-loop for instance. While the larger loops use an array with callbacks using a push/shift approach.

### Looped versus linear threading model

In the linear threading model each thread gets its own call-stack. While in the looped threading model all threads share the same call-stack. In the linear model state may be preserved on the call-stack. In the looped model all state must be encapsulated in an object. The linear model has a request/response feel, while the looped model as a more message or event feel.

The linear model seems to break the open/close principle. As the thread itself is not closed to modification. This is mostly noticeble when strictly defining exceptions in a function signature. A function may need to change its signature if lower functions start throwing exceptions of a different type. Or if exceptions are strictly handled it could break single responsibility as functions must now deal with foreign exceptions of lower functions to prevent signature change. The looped model does not seem to have this problem.

The looped model can be implemented without special support of a language. Except that back traces of exceptions are rather meaningless. However, it seems to require thinking more in terms of space and time rather than just space as with the linear model. So it is some what more complex. But in my opion less complex then scaling up the linear model.
