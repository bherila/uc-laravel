'use client'

import React, { useState, useEffect } from 'react'

interface Props {
  utcDate: string
}

const Countdown: React.FC<Props> = ({ utcDate }) => {
  const [tick, setTick] = useState(0)
  const [countdown, setCountdown] = useState('')
  const [targetDate, setTargetDate] = useState<Date | null>(null)

  useEffect(() => {
    const date = new Date(utcDate)
    setTargetDate(date)
  }, [utcDate])

  useEffect(() => {
    const intervalId = setInterval(() => {
      setTick((t) => t + 1)
    }, 1000)
    return () => clearInterval(intervalId)
  }, [])

  useEffect(() => {
    if (targetDate && !isNaN(targetDate.getTime())) {
      const now = new Date()
      const diff = targetDate.getTime() - now.getTime()
      const absDiff = Math.abs(diff)

      const days = Math.floor(absDiff / (1000 * 60 * 60 * 24))
      const hours = Math.floor((absDiff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60))
      const minutes = Math.floor((absDiff % (1000 * 60 * 60)) / (1000 * 60))
      const seconds = Math.floor((absDiff % (1000 * 60)) / 1000)

      if (absDiff > 86400000) {
        // 1 day in milliseconds
        if (diff > 0) {
          setCountdown(`in ${days}d, ${hours}h`)
        } else {
          setCountdown(`${days}d, ${hours}h ago`)
        }
      } else if (absDiff > 3600000) {
        // 1 hour in milliseconds
        if (diff > 0) {
          setCountdown(`in ${hours}h, ${minutes}m`)
        } else {
          setCountdown(`${hours}h, ${minutes}m ago`)
        }
      } else if (absDiff > 60000) {
        // 1 minute in milliseconds
        if (diff > 0) {
          setCountdown(`in ${minutes}m, ${seconds}s`)
        } else {
          setCountdown(`${minutes}m, ${seconds}s ago`)
        }
      } else {
        if (diff > 0) {
          setCountdown(`in ${seconds}s`)
        } else {
          setCountdown(`${seconds}s ago`)
        }
      }
    }
  }, [tick, targetDate])

  return !targetDate || isNaN(targetDate.getTime()) ? null : <span>{countdown}</span>
}

export default Countdown
