# Pathetique
An object-oriented path manipulation library in PHP.

The API and implementation is largely based on the [rust `std::path` implementation](https://github.com/rust-lang/rust/blob/d8bdb3fdcbd88eb16e1a6669236122c41ed2aed3/src/libstd/path.rs).

(I was listening to Tchaikovsky's Pathetique when I wrote this)

## Platform behaviour
Paths can be constructed for a specific `Platform` (either Windows or Unix)
such that Windows paths can be interpreted correctly on Unix systems
(although, apparently, they cannot be used to interact with the filesystem).

All methods that interact with the filesystem would throw a `PlatformMismatchException`
if a path for a different platform is used.
This will never happen if all paths are costructed with specifying a platform.

Some relative paths may be converted across platforms using `$path->toCurrentPlatform()`.

## Serialization
The path can be serialized using the PHP `serialize()` function.
The serialized data include the platform that the path is constructed for,
so paths can be deserialized on a different platform
and still retain some correct behaviour.
