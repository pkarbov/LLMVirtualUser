export function humanFileSize(bytes, si = false, decimals = 2) {

    if (!+bytes) return '0 Bytes'

    const k = si ? 1000 : 1024
    const dm = decimals < 0 ? 0 : decimals
    const sizes = si
        ? ['Bytes', 'KB',  'MB',  'GB',  'TB',  'PB',  'EB',  'ZB',  'YB']
        : ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB', 'EiB', 'ZiB', 'YiB']

    const i = Math.floor(Math.log(bytes) / Math.log(k))

    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(dm))} ${sizes[i]}`
}

export function humanModelType(type) {
    switch (type) {
      case 0 :
        return 'ALL_F32'
      case 1 :
        return 'F16'
      case 2 :
        return 'Q4_0'
      case 3 :
        return 'Q4_1'
       case 4 :
        return 'Q4_1_SOME_F16'
      case 7 :
        return 'Q8_0'
      case 8 :
        return 'Q5_0'
      case 9 :
        return 'Q5_1'
      case 10 :
        return 'Q2_K'
      case 11 :
        return 'Q3_K_S'
      case 12 :
        return 'Q3_K_M'
      case 13 :
        return 'Q3_K_L'
      case 14 :
        return 'Q4_K_S'
      case 15 :
        return 'Q4_K_M'
      case 16 :
        return 'Q5_K_S'
      case 17 :
        return 'Q5_K_M'
      case 18 :
        return 'Q6_K'
    }
    return 'UNK'
}
