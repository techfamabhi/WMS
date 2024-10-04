// javascript to sleep for seconds
const sleep = (seconds) => {
    const waitUntil = new Date().getTime() + seconds * 1000
    while(new Date().getTime() < waitUntil) {
        // do nothing
    }
}
